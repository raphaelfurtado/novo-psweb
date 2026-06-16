<?php

namespace App\Services;

use App\Models\Acordo;
use App\Models\ConciliacaoLote;
use App\Models\Pagamento;
use App\Models\Recebimento;
use App\Models\User;
use App\Support\AlocacaoFifo;
use App\Support\OfxParser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Conciliação bancária por OFX: importa o extrato, casa cada crédito com o
 * morador (por CPF, depois por nome) e dá baixa nas parcelas pela regra FIFO
 * (mais antiga primeiro). Toda a parte de "adivinhar o mês" é resolvida aqui.
 */
class ConciliacaoService
{
    /** PIX em forma_pagamento.codigo (varchar). */
    public const FORMA_PIX = '3';

    private const TOLERANCIA = 0.005;

    // ---------------------------------------------------------------- Importação

    /** Lê o conteúdo de um arquivo OFX, cria o lote e os recebimentos (já casados). */
    public function importar(string $conteudo, string $arquivo, ?int $userId): ConciliacaoLote
    {
        $parsed = OfxParser::parse($conteudo);
        $conta = $parsed['conta'];
        $moradores = $this->indexarMoradores();

        return DB::transaction(function () use ($parsed, $conta, $arquivo, $userId, $moradores) {
            $lote = ConciliacaoLote::create([
                'arquivo' => $arquivo,
                'banco' => $conta['banco'],
                'conta' => $conta['conta'],
                'periodo_inicio' => $conta['inicio'],
                'periodo_fim' => $conta['fim'],
                'id_importador' => $userId,
            ]);

            $qtd = 0;
            $total = 0.0;

            foreach ($parsed['transacoes'] as $t) {
                $fitid = $t['fitid'] ?: null;

                // Dedupe: a mesma transação (FITID) nunca entra duas vezes.
                if ($fitid && Recebimento::where('fitid', $fitid)->exists()) {
                    continue;
                }

                $nome = $t['nome'] ?: $t['memo'];
                $cpf = $this->cpfDoTexto(($t['memo'] ?? '') . ' ' . ($t['nome'] ?? ''));
                $morador = $this->casar($cpf, $nome, $moradores);

                Recebimento::create([
                    'id_lote' => $lote->id,
                    'fitid' => $fitid,
                    'data' => $t['data'],
                    'valor' => $t['valor'],
                    'nome_pagador' => $nome ? mb_substr($nome, 0, 255) : null,
                    'cpf_pagador' => $cpf,
                    'memo' => $t['memo'] ? mb_substr($t['memo'], 0, 255) : null,
                    'tipo' => $t['tipo'],
                    'id_usuario' => $morador?->id,
                    'status' => Recebimento::STATUS_PENDENTE,
                ]);

                $qtd++;
                $total += $t['valor'];
            }

            $lote->update(['qtd_transacoes' => $qtd, 'total_creditos' => $total]);

            return $lote;
        });
    }

    // ------------------------------------------------------------------ Sugestão

    /**
     * Sugestão de baixa para um recebimento (usada na tela de revisão).
     * status: 'ok' (verde, casou exato), 'acordo' (há acordo aberto que bate),
     *         'revisar' (amarelo, precisa de decisão), 'sem_morador'.
     *
     * @return array{status:string, morador:?User, parcelas:Collection, alocacao:?array, acordo:?Acordo}
     */
    public function sugestao(Recebimento $r): array
    {
        $morador = $r->usuario;

        if (! $morador) {
            return ['status' => 'sem_morador', 'morador' => null, 'parcelas' => collect(), 'alocacao' => null, 'acordo' => null];
        }

        $acordo = $morador->acordos()->aberto()->get()
            ->first(fn (Acordo $a) => $this->valorBate((float) $a->saldo, (float) $r->valor));

        $parcelas = $this->parcelasFifo($morador);
        $alocacao = AlocacaoFifo::alocar(
            (float) $r->valor,
            $parcelas->map(fn (Pagamento $p) => ['id' => $p->id, 'saldo' => $p->saldo_aberto])->all()
        );

        $status = match (true) {
            (bool) $acordo => 'acordo',
            $alocacao['integral'] => 'ok',
            default => 'revisar',
        };

        return compact('status', 'morador', 'parcelas', 'alocacao', 'acordo');
    }

    /** Parcelas elegíveis do morador, em ordem FIFO, com o saldo em aberto calculado. */
    public function parcelasFifo(User $morador): Collection
    {
        return $morador->pagamentos()
            ->disponivelParaConciliacao()
            ->with('recebimentos')
            ->orderByRaw('CONCAT(SUBSTR(referencia, 3, 4), SUBSTR(referencia, 1, 2)) asc')
            ->orderBy('data_vencimento')
            ->get()
            ->each(fn (Pagamento $p) => $p->saldo_aberto = round((float) $p->valor - $p->valor_pago, 2))
            ->filter(fn (Pagamento $p) => $p->saldo_aberto > self::TOLERANCIA)
            ->values();
    }

    // -------------------------------------------------------------------- Baixa

    /**
     * Dá baixa: aplica o recebimento em parcelas e marca como PAGO as que ficarem
     * totalmente cobertas. Parcial (cobertura < valor) mantém a parcela em ABERTO.
     *
     * @param  array<int, array{id:int, valor:float}>  $aplicacoes
     */
    public function conciliar(Recebimento $r, array $aplicacoes, float $credito = 0.0, ?int $userId = null): void
    {
        if (! $r->isPendente()) {
            return;
        }

        DB::transaction(function () use ($r, $aplicacoes, $credito, $userId) {
            foreach ($aplicacoes as $ap) {
                if (($ap['valor'] ?? 0) <= 0) {
                    continue;
                }
                $this->aplicarEmParcela($r, (int) $ap['id'], (float) $ap['valor']);
            }

            $r->update([
                'status' => Recebimento::STATUS_CONCILIADO,
                'valor_credito' => round($credito, 2),
                'conciliado_em' => now(),
                'id_conciliador' => $userId,
            ]);
        });
    }

    /** Quita um acordo aberto usando este recebimento (à vista ou somando parcelas do acordo). */
    public function conciliarComAcordo(Recebimento $r, Acordo $acordo, ?int $userId = null): void
    {
        if (! $r->isPendente() || ! $acordo->isAberto()) {
            return;
        }

        DB::transaction(function () use ($r, $acordo, $userId) {
            app(AcordoService::class)->registrarPagamento($acordo, (float) $r->valor, $r->data, $userId);

            $r->update([
                'id_acordo' => $acordo->id,
                'id_usuario' => $acordo->id_usuario,
                'status' => Recebimento::STATUS_CONCILIADO,
                'conciliado_em' => now(),
                'id_conciliador' => $userId,
            ]);
        });
    }

    public function ignorar(Recebimento $r, ?string $motivo = null, ?int $userId = null): void
    {
        if (! $r->isPendente()) {
            return;
        }

        $r->update([
            'status' => Recebimento::STATUS_IGNORADO,
            'observacao' => $motivo,
            'conciliado_em' => now(),
            'id_conciliador' => $userId,
        ]);
    }

    /** Desfaz uma conciliação: solta as parcelas (volta a ABERTO) e reabre acordo, se houver. */
    public function desfazer(Recebimento $r): void
    {
        DB::transaction(function () use ($r) {
            if ($r->id_acordo && ($acordo = $r->acordo)) {
                app(AcordoService::class)->estornarPagamento($acordo, (float) $r->valor);
            }

            foreach ($r->parcelas as $parcela) {
                $r->parcelas()->detach($parcela->id);
                $this->reavaliarParcela($parcela->id);
            }

            $r->update([
                'status' => Recebimento::STATUS_PENDENTE,
                'id_acordo' => null,
                'valor_credito' => 0,
                'observacao' => null,
                'conciliado_em' => null,
                'id_conciliador' => null,
            ]);
        });
    }

    // ----------------------------------------------------------------- Internos

    private function aplicarEmParcela(Recebimento $r, int $idPagamento, float $valor): void
    {
        $parcela = Pagamento::find($idPagamento);
        if (! $parcela) {
            return;
        }

        $r->parcelas()->syncWithoutDetaching([
            $idPagamento => ['valor_aplicado' => round($valor, 2)],
        ]);

        $this->reavaliarParcela($idPagamento, $r->data);
    }

    /** Recalcula a situação da parcela a partir do total aplicado pelos recebimentos. */
    private function reavaliarParcela(int $idPagamento, ?Carbon $dataPagamento = null): void
    {
        $parcela = Pagamento::find($idPagamento);
        if (! $parcela) {
            return;
        }

        $aplicado = (float) DB::table('recebimento_parcela')
            ->where('id_pagamento', $idPagamento)
            ->sum('valor_aplicado');

        if ($aplicado + self::TOLERANCIA >= (float) $parcela->valor) {
            $parcela->update([
                'situacao' => 'PAGO',
                'data_pagamento' => $dataPagamento ?? $parcela->data_pagamento ?? now(),
                'id_forma_pagamento' => $parcela->id_forma_pagamento ?: self::FORMA_PIX,
            ]);
        } else {
            // Cobertura parcial ou estorno: volta/segue em ABERTO (saldo derivado do pivot).
            $parcela->update([
                'situacao' => 'ABERTO',
                'data_pagamento' => $aplicado > 0 ? ($parcela->data_pagamento ?? $dataPagamento) : null,
            ]);
        }
    }

    // ----------------------------------------------------------------- Matching

    /** Carrega os moradores uma vez, já com nome normalizado para o casamento. */
    private function indexarMoradores(): Collection
    {
        return User::query()
            ->select('id', 'nome', 'cpf')
            ->get()
            ->each(fn (User $u) => $u->nome_normalizado = self::normalizar($u->nome));
    }

    private function casar(?string $cpf, ?string $nome, Collection $moradores): ?User
    {
        // 1) CPF exato (chave mais confiável).
        if ($cpf && strlen($cpf) === 11) {
            $porCpf = $moradores->firstWhere('cpf', $cpf);
            if ($porCpf) {
                return $porCpf;
            }
        }

        // 2) Nome: igualdade normalizada ou nome do morador contido no texto do pagador.
        if (! $nome) {
            return null;
        }

        $alvo = self::normalizar($nome);
        if ($alvo === '') {
            return null;
        }

        $exato = $moradores->firstWhere('nome_normalizado', $alvo);
        if ($exato) {
            return $exato;
        }

        $candidatos = $moradores->filter(function (User $u) use ($alvo) {
            $n = $u->nome_normalizado;

            return $n !== '' && (str_contains($alvo, $n) || str_contains($n, $alvo));
        });

        // Só casa por contém quando é inequívoco (um único candidato).
        return $candidatos->count() === 1 ? $candidatos->first() : null;
    }

    /** Primeiro CPF (11 dígitos, não mascarado) encontrado no texto. */
    private function cpfDoTexto(string $texto): ?string
    {
        if (preg_match('/(?<!\d)(\d{3}\.?\d{3}\.?\d{3}-?\d{2})(?!\d)/', $texto, $m)) {
            $digitos = preg_replace('/\D/', '', $m[1]);

            return strlen($digitos) === 11 ? $digitos : null;
        }

        return null;
    }

    private static function normalizar(?string $s): string
    {
        $s = mb_strtoupper(trim((string) $s));
        $s = preg_replace('/\s+/', ' ', $s);
        $semAcento = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);

        return $semAcento !== false ? preg_replace('/[^A-Z ]/', '', strtoupper($semAcento)) : $s;
    }

    private function valorBate(float $a, float $b): bool
    {
        return abs($a - $b) < self::TOLERANCIA;
    }
}

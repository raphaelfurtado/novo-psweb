<?php

namespace App\Services;

use App\Models\Acordo;
use App\Models\Pagamento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Acordos de parcelas atrasadas: criação (com desconto/parcelado), registro de
 * pagamento (manual ou via conciliação OFX) e quitação — que dá baixa nas
 * parcelas cobertas. Enquanto ABERTO, as parcelas ficam reservadas.
 */
class AcordoService
{
    public const FORMA_PIX = '3';

    private const TOLERANCIA = 0.005;

    /**
     * Cria um acordo cobrindo as parcelas informadas (do próprio morador).
     * valor_original = soma dos saldos; desconto = original - acordado.
     *
     * @param  array<int>  $idParcelas
     */
    public function criar(
        User $morador,
        array $idParcelas,
        float $valorAcordado,
        int $numParcelas = 1,
        ?string $observacao = null,
        ?int $userId = null,
        ?Carbon $dataAcordo = null
    ): Acordo {
        $parcelas = $morador->pagamentos()
            ->disponivelParaConciliacao()
            ->whereIn('id', $idParcelas)
            ->with('recebimentos')
            ->get();

        $original = round($parcelas->sum(fn (Pagamento $p) => (float) $p->valor - $p->valor_pago), 2);
        $valorAcordado = round($valorAcordado, 2);
        $desconto = max(0, round($original - $valorAcordado, 2));

        return DB::transaction(function () use ($morador, $parcelas, $original, $valorAcordado, $desconto, $numParcelas, $observacao, $userId, $dataAcordo) {
            $acordo = Acordo::create([
                'id_usuario' => $morador->id,
                'valor_original' => $original,
                'valor_acordado' => $valorAcordado,
                'desconto' => $desconto,
                'num_parcelas' => max(1, $numParcelas),
                'valor_pago' => 0,
                'situacao' => Acordo::SITUACAO_ABERTO,
                'data_acordo' => $dataAcordo ?? today(),
                'observacao' => $observacao,
                'id_criador' => $userId,
            ]);

            $acordo->parcelas()->sync($parcelas->pluck('id')->all());

            return $acordo;
        });
    }

    /** Registra um pagamento no acordo (dinheiro ou OFX). Quita quando o total acordado é atingido. */
    public function registrarPagamento(Acordo $acordo, float $valor, ?Carbon $data = null, ?int $userId = null): void
    {
        if (! $acordo->isAberto()) {
            return;
        }

        $novoPago = round((float) $acordo->valor_pago + $valor, 2);
        $acordo->update(['valor_pago' => $novoPago]);

        if ($novoPago + self::TOLERANCIA >= (float) $acordo->valor_acordado) {
            $this->quitar($acordo, $data ?? today());
        }
    }

    /** Estorna um pagamento (usado ao desfazer um recebimento conciliado). */
    public function estornarPagamento(Acordo $acordo, float $valor): void
    {
        $estavaQuitado = $acordo->situacao === Acordo::SITUACAO_QUITADO;
        $novoPago = max(0, round((float) $acordo->valor_pago - $valor, 2));

        $acordo->update([
            'valor_pago' => $novoPago,
            'situacao' => Acordo::SITUACAO_ABERTO,
            'quitado_em' => null,
        ]);

        if ($estavaQuitado) {
            $acordo->parcelas()->each(fn (Pagamento $p) => $p->update([
                'situacao' => 'ABERTO',
                'data_pagamento' => null,
            ]));
        }
    }

    /** Quita o acordo: marca todas as parcelas cobertas como PAGO. */
    public function quitar(Acordo $acordo, ?Carbon $data = null): void
    {
        $data ??= today();

        DB::transaction(function () use ($acordo, $data) {
            foreach ($acordo->parcelas as $parcela) {
                if ($parcela->situacao !== 'PAGO') {
                    $parcela->update([
                        'situacao' => 'PAGO',
                        'data_pagamento' => $data,
                        'id_forma_pagamento' => $parcela->id_forma_pagamento ?: self::FORMA_PIX,
                    ]);
                }
            }

            $acordo->update([
                'situacao' => Acordo::SITUACAO_QUITADO,
                'quitado_em' => now(),
                'valor_pago' => max((float) $acordo->valor_pago, (float) $acordo->valor_acordado),
            ]);
        });
    }

    /** Cancela o acordo: libera as parcelas (voltam ao FIFO da conciliação). */
    public function cancelar(Acordo $acordo): void
    {
        $acordo->update(['situacao' => Acordo::SITUACAO_CANCELADO]);
    }
}

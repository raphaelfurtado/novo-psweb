<?php

namespace App\Livewire\Conciliacao;

use App\Models\ConciliacaoLote;
use App\Models\Recebimento;
use App\Models\User;
use App\Services\ConciliacaoService;
use App\Support\AlocacaoFifo;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Tela de revisão/baixa de um lote: cada crédito do extrato com sua sugestão
 * (FIFO), ações de confirmar/ignorar/desfazer e um painel de ajuste manual.
 */
class Revisar extends Component
{
    public ConciliacaoLote $lote;

    // Painel de ajuste
    public bool $mostrarAjuste = false;
    public ?int $ajusteId = null;
    public ?int $ajusteMoradorId = null;
    public array $ajusteParcelas = [];      // [id_pagamento => bool]
    public string $ajusteAcao = 'parcelas'; // 'parcelas' | 'ignorar'
    public string $ajusteMotivo = '';

    public function mount(ConciliacaoLote $lote): void
    {
        $this->lote = $lote;
    }

    private function svc(): ConciliacaoService
    {
        return app(ConciliacaoService::class);
    }

    private function recebimento(?int $id): ?Recebimento
    {
        return $id ? Recebimento::where('id_lote', $this->lote->id)->find($id) : null;
    }

    // ----------------------------------------------------------- Ações diretas

    public function confirmar(int $id): void
    {
        $r = $this->recebimento($id);
        if (! $r || ! $r->isPendente()) {
            return;
        }

        $sug = $this->svc()->sugestao($r);

        if ($sug['status'] === 'acordo' && $sug['acordo']) {
            $this->svc()->conciliarComAcordo($r, $sug['acordo'], auth()->id());
        } elseif ($sug['alocacao'] && count($sug['alocacao']['aplicacoes'])) {
            $this->svc()->conciliar($r, $sug['alocacao']['aplicacoes'], $sug['alocacao']['credito'], auth()->id());
        } else {
            session()->flash('error', 'Sem sugestão automática para este recebimento — use Ajustar.');

            return;
        }

        session()->flash('success', 'Baixa realizada com sucesso.');
    }

    public function confirmarVerdes(): void
    {
        $n = 0;

        foreach ($this->lote->recebimentos()->pendente()->get() as $r) {
            $sug = $this->svc()->sugestao($r);
            if ($sug['status'] === 'ok' && $sug['alocacao']) {
                $this->svc()->conciliar($r, $sug['alocacao']['aplicacoes'], $sug['alocacao']['credito'], auth()->id());
                $n++;
            }
        }

        session()->flash($n ? 'success' : 'error', $n
            ? "{$n} baixa(s) automática(s) realizada(s)."
            : 'Nenhum recebimento estava pronto para baixa automática.');
    }

    public function ignorar(int $id): void
    {
        $r = $this->recebimento($id);
        if ($r) {
            $this->svc()->ignorar($r, null, auth()->id());
        }
    }

    public function desfazer(int $id): void
    {
        $r = $this->recebimento($id);
        if ($r) {
            $this->svc()->desfazer($r);
            session()->flash('success', 'Conciliação desfeita — parcelas voltaram a ABERTO.');
        }
    }

    // ------------------------------------------------------------ Painel ajuste

    public function abrirAjuste(int $id): void
    {
        $r = $this->recebimento($id);
        if (! $r || ! $r->isPendente()) {
            return;
        }

        $this->ajusteId = $id;
        $this->ajusteAcao = 'parcelas';
        $this->ajusteMotivo = '';
        $this->ajusteMoradorId = $r->id_usuario;
        $this->carregarParcelasAjuste(true);
        $this->mostrarAjuste = true;
    }

    public function updatedAjusteMoradorId(): void
    {
        $this->carregarParcelasAjuste(false);
    }

    private function carregarParcelasAjuste(bool $preselecionar): void
    {
        $this->ajusteParcelas = [];

        $morador = $this->ajusteMoradorId ? User::find($this->ajusteMoradorId) : null;
        if (! $morador) {
            return;
        }

        $parcelas = $this->svc()->parcelasFifo($morador);
        $r = $this->recebimento($this->ajusteId);
        $preSelecionados = [];

        if ($preselecionar && $r) {
            $aloc = AlocacaoFifo::alocar(
                (float) $r->valor,
                $parcelas->map(fn ($p) => ['id' => $p->id, 'saldo' => $p->saldo_aberto])->all()
            );
            $preSelecionados = collect($aloc['aplicacoes'])->pluck('id')->all();
        }

        foreach ($parcelas as $p) {
            $this->ajusteParcelas[$p->id] = in_array($p->id, $preSelecionados, true);
        }
    }

    public function salvarAjuste(): void
    {
        $r = $this->recebimento($this->ajusteId);
        if (! $r || ! $r->isPendente()) {
            $this->fecharAjuste();

            return;
        }

        if ($this->ajusteAcao === 'ignorar') {
            $this->svc()->ignorar($r, $this->ajusteMotivo ?: null, auth()->id());
            $this->fecharAjuste();
            session()->flash('success', 'Recebimento marcado como ignorado.');

            return;
        }

        if ($this->ajusteMoradorId && $this->ajusteMoradorId !== $r->id_usuario) {
            $r->update(['id_usuario' => $this->ajusteMoradorId]);
            $r->refresh();
        }

        $morador = $r->usuario;
        if (! $morador) {
            session()->flash('error', 'Selecione o morador antes de confirmar.');

            return;
        }

        $escolhidas = $this->svc()->parcelasFifo($morador)
            ->filter(fn ($p) => ! empty($this->ajusteParcelas[$p->id]))
            ->values();

        if ($escolhidas->isEmpty()) {
            session()->flash('error', 'Selecione ao menos uma parcela (ou use "Ignorar").');

            return;
        }

        $aloc = AlocacaoFifo::alocar(
            (float) $r->valor,
            $escolhidas->map(fn ($p) => ['id' => $p->id, 'saldo' => $p->saldo_aberto])->all()
        );

        $this->svc()->conciliar($r, $aloc['aplicacoes'], $aloc['credito'], auth()->id());
        $this->fecharAjuste();
        session()->flash('success', 'Baixa realizada com sucesso.');
    }

    public function fecharAjuste(): void
    {
        $this->mostrarAjuste = false;
        $this->ajusteId = null;
        $this->ajusteParcelas = [];
        $this->ajusteMoradorId = null;
    }

    public function render()
    {
        $recebimentos = $this->lote->recebimentos()
            ->with(['usuario', 'parcelas', 'acordo'])
            ->orderBy('data')->orderBy('id')
            ->get();

        $itens = $recebimentos->map(function (Recebimento $r) {
            return ['r' => $r, 'sug' => $r->isPendente() ? $this->svc()->sugestao($r) : null];
        });

        $resumo = [
            'total' => $recebimentos->count(),
            'pendentes' => $recebimentos->where('status', Recebimento::STATUS_PENDENTE)->count(),
            'conciliados' => $recebimentos->where('status', Recebimento::STATUS_CONCILIADO)->count(),
            'ignorados' => $recebimentos->where('status', Recebimento::STATUS_IGNORADO)->count(),
            'verdes' => $itens->filter(fn ($i) => ($i['sug']['status'] ?? null) === 'ok')->count(),
        ];

        // Painel de ajuste — parcelas e resumo do valor selecionado
        $ajusteParcelasInfo = collect();
        $ajusteResumo = null;
        if ($this->mostrarAjuste && $this->ajusteMoradorId) {
            $morador = User::find($this->ajusteMoradorId);
            $r = $this->recebimento($this->ajusteId);
            if ($morador && $r) {
                $ajusteParcelasInfo = $this->svc()->parcelasFifo($morador);
                $somaSel = round(
                    $ajusteParcelasInfo->filter(fn ($p) => ! empty($this->ajusteParcelas[$p->id]))->sum('saldo_aberto'),
                    2
                );
                $ajusteResumo = [
                    'recebido' => (float) $r->valor,
                    'selecionado' => $somaSel,
                    'diferenca' => round((float) $r->valor - $somaSel, 2),
                ];
            }
        }

        return view('livewire.conciliacao.revisar', [
            'itens' => $itens,
            'resumo' => $resumo,
            'moradores' => $this->moradores(),
            'ajusteParcelasInfo' => $ajusteParcelasInfo,
            'ajusteResumo' => $ajusteResumo,
        ]);
    }

    private function moradores(): Collection
    {
        return User::orderBy('nome')->get(['id', 'nome']);
    }
}

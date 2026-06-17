<?php

namespace App\Livewire\Morador;

use App\Models\Pagamento;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

class MeusPagamentos extends Component
{
    #[Url]
    public string $ano = '';

    #[Url]
    public string $situacao = '';

    public function mount(): void
    {
        $this->ano = $this->ano ?: Carbon::now()->format('Y');
    }

    protected function baseQuery()
    {
        return Pagamento::query()
            ->where('id_usuario', auth()->id())
            ->when($this->ano, fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$this->ano]))
            ->when($this->situacao === 'VENCIDO', fn ($q) => $q->vencido())
            ->when($this->situacao !== '' && $this->situacao !== 'VENCIDO',
                fn ($q) => $q->where('situacao', $this->situacao));
    }

    public function render()
    {
        $pagamentos = $this->baseQuery()
            ->with(['tipoPagamento', 'formaPagamento', 'comprovantes'])
            // Referência crescente (ano, depois mês) — ordem cronológica.
            ->orderByRaw('SUBSTR(referencia, 3, 4) ASC, SUBSTR(referencia, 1, 2) ASC')
            ->orderBy('id')
            ->get();

        return view('livewire.morador.meus-pagamentos', [
            'pagamentos' => $pagamentos,
            'totalPago' => (clone $this->baseQuery())->where('situacao', 'PAGO')->sum('valor'),
            'totalAberto' => (clone $this->baseQuery())->whereIn('situacao', ['ABERTO', 'PENDENTE'])->sum('valor'),
            'totalLista' => (float) $pagamentos->sum('valor'),
            'anos' => $this->anosDisponiveis(),
        ]);
    }

    protected function anosDisponiveis(): array
    {
        $anos = Pagamento::where('id_usuario', auth()->id())
            ->selectRaw('DISTINCT SUBSTR(referencia, 3, 4) as ano')
            ->orderByDesc('ano')->pluck('ano')->filter()->values()->all();

        $atual = Carbon::now()->format('Y');
        if (! in_array($atual, $anos)) {
            array_unshift($anos, $atual);
        }

        return $anos;
    }
}

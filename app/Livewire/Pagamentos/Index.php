<?php

namespace App\Livewire\Pagamentos;

use App\Livewire\Concerns\HandlesAnexos;
use App\Models\Pagamento;
use App\Models\TipoPagamento;
use App\Support\Referencia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HandlesAnexos;
    use WithPagination;

    #[Url]
    public string $ano = '';

    #[Url]
    public string $mes = '';

    #[Url]
    public string $situacao = '';

    #[Url]
    public string $tipo = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $quadra = '';

    #[Url]
    public string $numero = '';

    public function mount(): void
    {
        $this->ano = $this->ano ?: Carbon::now()->format('Y');
    }

    public function updated($name): void
    {
        if (in_array($name, ['ano', 'mes', 'situacao', 'tipo', 'search', 'quadra', 'numero'])) {
            $this->resetPage();
        }
    }

    public function excluir(int $id): void
    {
        $pag = Pagamento::findOrFail($id);
        $this->removerAnexosDe('PAGAMENTO', $pag->id);
        $pag->delete();

        session()->flash('success', 'Pagamento excluído com sucesso.');
    }

    /** Baixa rápida: marca um lançamento em aberto como PAGO na data de hoje. */
    public function darBaixa(int $id): void
    {
        $pag = Pagamento::findOrFail($id);

        if (in_array($pag->situacao, ['ABERTO', 'PENDENTE'], true)) {
            $pag->update(['situacao' => 'PAGO', 'data_pagamento' => now()]);
            session()->flash('success', 'Pagamento baixado (PAGO) com sucesso.');
        }
    }

    /** Parâmetros (filtros atuais) para os links de exportação PDF/Excel. */
    public function paramsExport(string $fmt): array
    {
        return ['fmt' => $fmt] + $this->filtrosAtuais();
    }

    /** Filtros atuais (não vazios) — usados para preservar o contexto ao editar/voltar. */
    public function filtrosAtuais(): array
    {
        return array_filter([
            'q' => $this->search,
            'ano' => $this->ano,
            'mes' => $this->mes,
            'situacao' => $this->situacao,
            'tipo' => $this->tipo,
            'quadra' => $this->quadra,
            'numero' => $this->numero,
        ], fn ($v) => $v !== '' && $v !== null);
    }

    protected function baseQuery()
    {
        return Pagamento::query()
            ->when($this->ano, fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$this->ano]))
            ->when($this->mes, fn ($q) => $q->whereRaw('SUBSTR(referencia, 1, 2) = ?', [$this->mes]))
            ->when($this->situacao === 'VENCIDO', fn ($q) => $q->vencido())
            ->when($this->situacao !== '' && $this->situacao !== 'VENCIDO',
                fn ($q) => $q->where('situacao', $this->situacao))
            ->when($this->tipo !== '', fn ($q) => $q->where('id_tipo_pagamento', $this->tipo))
            ->when($this->search, fn ($q) => $q->whereHas('usuario',
                fn ($q) => $q->where('nome', 'like', "%{$this->search}%")))
            ->when($this->quadra !== '', fn ($q) => $q->whereHas('usuario.endereco',
                fn ($q) => $q->where('quadra', $this->quadra)))
            ->when($this->numero !== '', fn ($q) => $q->whereHas('usuario.endereco',
                fn ($q) => $q->where('numero', $this->numero)));
    }

    public function render()
    {
        $pagamentos = $this->baseQuery()
            ->with(['usuario.endereco', 'tipoPagamento', 'formaPagamento', 'recebedor', 'comprovantes'])
            // Ordena por referência crescente (ano, depois mês) — ordem cronológica.
            ->orderByRaw('SUBSTR(referencia, 3, 4) ASC, SUBSTR(referencia, 1, 2) ASC')
            ->orderBy('id')
            ->paginate(15);

        return view('livewire.pagamentos.index', [
            'pagamentos' => $pagamentos,
            'totalFiltro' => $this->baseQuery()->sum('valor'),
            'totalPago' => $this->baseQuery()->where('situacao', 'PAGO')->sum('valor'),
            'anos' => $this->anosDisponiveis(),
            'meses' => Referencia::MESES,
            'tipos' => TipoPagamento::orderBy('codigo')->get(),
            'quadras' => DB::table('endereco')->select('quadra')->distinct()->orderBy('quadra')->pluck('quadra'),
        ]);
    }

    protected function anosDisponiveis(): array
    {
        $anos = Pagamento::query()
            ->selectRaw('DISTINCT SUBSTR(referencia, 3, 4) as ano')
            ->orderByDesc('ano')
            ->pluck('ano')
            ->filter()
            ->values()
            ->all();

        $atual = Carbon::now()->format('Y');
        if (! in_array($atual, $anos)) {
            array_unshift($anos, $atual);
        }

        return $anos;
    }
}

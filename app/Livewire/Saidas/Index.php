<?php

namespace App\Livewire\Saidas;

use App\Livewire\Concerns\HandlesAnexos;
use App\Models\Saida;
use App\Models\TipoPagamento;
use App\Support\Referencia;
use Illuminate\Support\Carbon;
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
    public string $tipo = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->ano = $this->ano ?: Carbon::now()->format('Y');
    }

    public function updated($name): void
    {
        if (in_array($name, ['ano', 'mes', 'tipo', 'search'])) {
            $this->resetPage();
        }
    }

    public function excluir(int $id): void
    {
        $saida = Saida::despesa()->findOrFail($id);
        $this->removerAnexosDe('SAIDA', $saida->id);
        $saida->delete();

        session()->flash('success', 'Despesa excluída com sucesso.');
    }

    public function paramsExport(string $fmt): array
    {
        return ['fmt' => $fmt, 'ano' => $this->ano, 'mes' => $this->mes, 'tipo' => $this->tipo, 'q' => $this->search];
    }

    protected function baseQuery()
    {
        return Saida::query()
            ->despesa()
            ->when($this->ano, fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$this->ano]))
            ->when($this->mes, fn ($q) => $q->whereRaw('SUBSTR(referencia, 1, 2) = ?', [$this->mes]))
            ->when($this->tipo !== '', fn ($q) => $q->where('id_tipo_pagamento', $this->tipo))
            ->when($this->search, fn ($q) => $q->where('observacao', 'like', "%{$this->search}%"));
    }

    public function render()
    {
        $saidas = $this->baseQuery()
            ->with(['tipoPagamento', 'formaPagamento', 'recebedor', 'comprovantes'])
            ->orderByDesc('data_pagamento')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.saidas.index', [
            'saidas' => $saidas,
            'total' => $this->baseQuery()->sum('valor'),
            'anos' => $this->anosDisponiveis(),
            'meses' => Referencia::MESES,
            'tipos' => TipoPagamento::orderBy('codigo')->get(),
        ]);
    }

    protected function anosDisponiveis(): array
    {
        $anos = Saida::query()->selectRaw('DISTINCT SUBSTR(referencia, 3, 4) as ano')
            ->orderByDesc('ano')->pluck('ano')->filter()->values()->all();

        $atual = Carbon::now()->format('Y');
        if (! in_array($atual, $anos)) {
            array_unshift($anos, $atual);
        }

        return $anos;
    }
}

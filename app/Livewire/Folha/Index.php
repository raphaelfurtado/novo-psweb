<?php

namespace App\Livewire\Folha;

use App\Livewire\Concerns\HandlesAnexos;
use App\Models\Saida;
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

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->ano = $this->ano ?: Carbon::now()->format('Y');
    }

    public function updated($name): void
    {
        if (in_array($name, ['ano', 'mes', 'search'])) {
            $this->resetPage();
        }
    }

    public function excluir(int $id): void
    {
        $saida = Saida::deFolha()->findOrFail($id);
        $this->removerAnexosDe('PAGAMENTO_FUNC', $saida->id);
        $saida->delete();

        session()->flash('success', 'Pagamento de funcionário excluído.');
    }

    public function paramsExport(string $fmt): array
    {
        return ['fmt' => $fmt, 'ano' => $this->ano, 'mes' => $this->mes, 'q' => $this->search];
    }

    protected function baseQuery()
    {
        return Saida::query()
            ->deFolha()
            ->when($this->ano, fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$this->ano]))
            ->when($this->mes, fn ($q) => $q->whereRaw('SUBSTR(referencia, 1, 2) = ?', [$this->mes]))
            ->when($this->search, fn ($q) => $q->whereHas('funcionario',
                fn ($q) => $q->where('nome_completo', 'like', "%{$this->search}%")));
    }

    public function render()
    {
        $pagamentos = $this->baseQuery()
            ->with(['funcionario', 'tipoSaida', 'formaPagamento', 'comprovantes'])
            ->orderByDesc('data_pagamento')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.folha.index', [
            'pagamentos' => $pagamentos,
            'total' => $this->baseQuery()->sum('valor'),
            'anos' => $this->anosDisponiveis(),
            'meses' => Referencia::MESES,
        ]);
    }

    protected function anosDisponiveis(): array
    {
        $anos = Saida::deFolha()->selectRaw('DISTINCT SUBSTR(referencia, 3, 4) as ano')
            ->orderByDesc('ano')->pluck('ano')->filter()->values()->all();

        $atual = Carbon::now()->format('Y');
        if (! in_array($atual, $anos)) {
            array_unshift($anos, $atual);
        }

        return $anos;
    }
}

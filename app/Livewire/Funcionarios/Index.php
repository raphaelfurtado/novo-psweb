<?php

namespace App\Livewire\Funcionarios;

use App\Models\Funcionario;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function excluir(int $id): void
    {
        $f = Funcionario::findOrFail($id);

        if ($f->saidas()->exists()) {
            session()->flash('error', 'Não é possível excluir: há pagamentos de folha vinculados a este funcionário.');

            return;
        }

        $f->delete();
        session()->flash('success', 'Funcionário excluído com sucesso.');
    }

    public function render()
    {
        $rows = Funcionario::query()
            ->when($this->search, fn ($q) => $q->where('nome_completo', 'like', "%{$this->search}%")
                ->orWhere('cpf', 'like', "%{$this->search}%"))
            ->orderBy('nome_completo')
            ->paginate(10);

        return view('livewire.funcionarios.index', ['rows' => $rows]);
    }
}

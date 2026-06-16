<?php

namespace App\Livewire\Moradores;

use App\Livewire\Concerns\HandlesAnexos;
use App\Models\Anexo;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HandlesAnexos;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public bool $semPagamento = false;

    // Confirmação de exclusão
    public bool $showConfirm = false;
    public ?int $confirmandoId = null;
    public ?string $confirmandoNome = null;
    public int $confirmandoPagamentos = 0;
    public bool $excluirPagamentos = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSemPagamento(): void
    {
        $this->resetPage();
    }

    /** Abre o modal de confirmação, mostrando quantos pagamentos o morador possui. */
    public function confirmar(int $id): void
    {
        $user = User::withCount('pagamentos')->findOrFail($id);

        $this->confirmandoId = $user->id;
        $this->confirmandoNome = $user->nome;
        $this->confirmandoPagamentos = $user->pagamentos_count;
        $this->excluirPagamentos = false;
        $this->resetValidation();
        $this->showConfirm = true;
    }

    public function excluir(): void
    {
        if (! $this->confirmandoId) {
            return;
        }

        if ($this->confirmandoId === (int) auth()->id()) {
            session()->flash('error', 'Você não pode excluir o seu próprio usuário.');
            $this->showConfirm = false;

            return;
        }

        $user = User::findOrFail($this->confirmandoId);

        // Se há pagamentos e a opção não foi marcada, exige a confirmação explícita.
        if ($user->pagamentos()->exists() && ! $this->excluirPagamentos) {
            $this->addError('excluirPagamentos', 'Este morador possui pagamentos. Marque a opção para excluí-los junto.');

            return;
        }

        DB::transaction(function () use ($user) {
            if ($this->excluirPagamentos) {
                // Remove os comprovantes dos pagamentos e os próprios pagamentos.
                $pagIds = $user->pagamentos()->pluck('id');
                Anexo::where('form', 'PAGAMENTO')->whereIn('identifier', $pagIds)->get()
                    ->each(fn (Anexo $a) => $this->removerAnexo($a));
                $user->pagamentos()->delete();
            }

            // Anexos diretos do morador + endereço + usuário.
            Anexo::where('id_morador', $user->id)->get()->each(fn (Anexo $a) => $this->removerAnexo($a));
            $user->endereco()->delete();
            $user->delete();
        });

        $this->showConfirm = false;
        $this->reset('confirmandoId', 'confirmandoNome', 'confirmandoPagamentos', 'excluirPagamentos');
        session()->flash('success', 'Morador excluído com sucesso.');
    }

    public function render()
    {
        $ano = Carbon::now()->format('Y');

        $rows = User::query()
            ->with('endereco')
            ->withCount(['pagamentos as pagamentos_ano' => fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])])
            ->when($this->search, fn ($q) => $q->where('nome', 'like', "%{$this->search}%")
                ->orWhere('telefone', 'like', "%{$this->search}%"))
            ->when($this->semPagamento, fn ($q) => $q->whereDoesntHave('pagamentos',
                fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])))
            ->orderBy('nome')
            ->paginate(12);

        return view('livewire.moradores.index', [
            'rows' => $rows,
            'ano' => $ano,
        ]);
    }
}

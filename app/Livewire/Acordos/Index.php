<?php

namespace App\Livewire\Acordos;

use App\Models\Acordo;
use App\Services\AcordoService;
use App\Support\Valor;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lista de acordos com ações de registrar pagamento (dinheiro) e cancelar.
 */
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $situacao = '';

    #[Url(as: 'q')]
    public string $search = '';

    // Modal "registrar pagamento"
    public bool $mostrarPagamento = false;
    public ?int $pagamentoAcordoId = null;
    public string $pagamentoValor = '';

    public function updated($name): void
    {
        if (in_array($name, ['situacao', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function abrirPagamento(int $id): void
    {
        $acordo = Acordo::find($id);
        if (! $acordo || ! $acordo->isAberto()) {
            return;
        }

        $this->pagamentoAcordoId = $id;
        $this->pagamentoValor = Valor::br($acordo->saldo);
        $this->mostrarPagamento = true;
    }

    public function registrarPagamento(): void
    {
        $acordo = $this->pagamentoAcordoId ? Acordo::find($this->pagamentoAcordoId) : null;
        $valor = Valor::paraDecimal($this->pagamentoValor);

        if (! $acordo || ! $valor || $valor <= 0) {
            session()->flash('error', 'Informe um valor válido.');

            return;
        }

        app(AcordoService::class)->registrarPagamento($acordo, $valor, today(), auth()->id());

        $this->mostrarPagamento = false;
        $this->reset(['pagamentoAcordoId', 'pagamentoValor']);
        session()->flash('success', 'Pagamento do acordo registrado.');
    }

    public function cancelar(int $id): void
    {
        $acordo = Acordo::find($id);
        if ($acordo && $acordo->isAberto()) {
            app(AcordoService::class)->cancelar($acordo);
            session()->flash('success', 'Acordo cancelado — parcelas liberadas.');
        }
    }

    public function render()
    {
        $acordos = Acordo::query()
            ->with('usuario')
            ->withCount('parcelas')
            ->when($this->situacao, fn ($q) => $q->where('situacao', $this->situacao))
            ->when($this->search, fn ($q) => $q->whereHas('usuario', fn ($q) => $q->where('nome', 'like', "%{$this->search}%")))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.acordos.index', ['acordos' => $acordos]);
    }
}

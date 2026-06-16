<?php

namespace App\Livewire\Recebedores;

use App\Models\Pagamento;
use App\Models\Recebedor;
use App\Models\Saida;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;
    public string $nome = '';
    public string $telefone = '';
    public string $telefone_2 = '';
    public ?string $data_nascimento = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'nome' => ['required', 'max:50'],
            'telefone' => ['required', 'max:15'],
            'telefone_2' => ['nullable', 'max:15'],
            'data_nascimento' => ['nullable', 'date'],
        ];
    }

    protected function messages(): array
    {
        return ['required' => 'Campo obrigatório.'];
    }

    public function novo(): void
    {
        $this->reset('editingId', 'nome', 'telefone', 'telefone_2', 'data_nascimento');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function editar(int $id): void
    {
        $r = Recebedor::findOrFail($id);
        $this->editingId = $r->id;
        $this->nome = $r->nome;
        $this->telefone = $r->telefone;
        $this->telefone_2 = $r->telefone_2 ?? '';
        $this->data_nascimento = optional($r->data_nascimento)->format('Y-m-d');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function excluir(int $id): void
    {
        $recebedor = Recebedor::findOrFail($id);

        $emUso = Pagamento::where('id_recebedor', $id)->exists()
            || Saida::where('id_recebedor', $id)->exists();

        if ($emUso) {
            session()->flash('error', 'Não é possível excluir: este recebedor está vinculado a pagamentos ou saídas.');

            return;
        }

        $recebedor->delete();
        session()->flash('success', 'Recebedor excluído com sucesso.');
    }

    public function salvar(): void
    {
        $data = $this->validate();
        $data['nome'] = mb_strtoupper(trim($data['nome']));

        if ($this->editingId) {
            Recebedor::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Recebedor atualizado com sucesso.');
        } else {
            $data['data_insert'] = now();
            Recebedor::create($data);
            session()->flash('success', 'Recebedor cadastrado com sucesso.');
        }

        $this->showModal = false;
    }

    public function render()
    {
        $rows = Recebedor::query()
            ->when($this->search, fn ($q) => $q->where('nome', 'like', "%{$this->search}%")
                ->orWhere('telefone', 'like', "%{$this->search}%"))
            ->orderBy('nome')
            ->paginate(10);

        return view('livewire.recebedores.index', ['rows' => $rows]);
    }
}

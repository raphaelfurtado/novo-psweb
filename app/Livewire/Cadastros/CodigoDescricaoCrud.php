<?php

namespace App\Livewire\Cadastros;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CRUD base para as tabelas de domínio "codigo / descricao / registro_ativo"
 * (tipo_pagamento, forma_pagamento, tipo_saida). Lista reativa + modal + ativar/desativar.
 */
abstract class CodigoDescricaoCrud extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;
    public string $codigo = '';
    public string $descricao = '';

    abstract protected function modelClass(): string;

    abstract public function titulo(): string;

    abstract public function singular(): string;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function novo(): void
    {
        $this->reset('editingId', 'codigo', 'descricao');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function editar(int $id): void
    {
        $m = $this->modelClass()::findOrFail($id);
        $this->editingId = $m->id;
        $this->codigo = $m->codigo;
        $this->descricao = $m->descricao;
        $this->resetValidation();
        $this->showModal = true;
    }

    protected function rules(): array
    {
        $table = (new ($this->modelClass())())->getTable();

        return [
            'codigo' => ['required', 'max:2', Rule::unique($table, 'codigo')->ignore($this->editingId)],
            'descricao' => ['required', 'max:50'],
        ];
    }

    protected function messages(): array
    {
        return [
            'codigo.unique' => 'O código informado já está cadastrado.',
            'required' => 'Campo obrigatório.',
            'codigo.max' => 'O código deve ter no máximo 2 caracteres.',
        ];
    }

    public function salvar(): void
    {
        $data = $this->validate();
        $data['descricao'] = mb_strtoupper(trim($data['descricao']));

        if ($this->editingId) {
            $this->modelClass()::findOrFail($this->editingId)->update($data);
            session()->flash('success', $this->singular() . ' atualizado(a) com sucesso.');
        } else {
            $data['registro_ativo'] = 1;
            $data['data_insert'] = now();
            $this->modelClass()::create($data);
            session()->flash('success', $this->singular() . ' cadastrado(a) com sucesso.');
        }

        $this->showModal = false;
        $this->reset('editingId', 'codigo', 'descricao');
    }

    public function toggleAtivo(int $id): void
    {
        $m = $this->modelClass()::findOrFail($id);
        $m->registro_ativo = $m->registro_ativo ? 0 : 1;
        $m->save();

        session()->flash('success', 'Registro ' . ($m->registro_ativo ? 'ativado' : 'desativado') . '.');
    }

    public function render()
    {
        $rows = $this->modelClass()::query()
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('descricao', 'like', "%{$this->search}%")
                        ->orWhere('codigo', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('codigo')
            ->paginate(10);

        return view('livewire.cadastros.codigo-descricao', [
            'rows' => $rows,
            'titulo' => $this->titulo(),
            'singular' => $this->singular(),
        ]);
    }
}

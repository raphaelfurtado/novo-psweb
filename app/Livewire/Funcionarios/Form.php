<?php

namespace App\Livewire\Funcionarios;

use App\Models\Funcionario;
use App\Support\Valor;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Funcionario $funcionario = null;
    public bool $editando = false;

    // Dados pessoais
    public string $nome_completo = '';
    public ?string $data_nascimento = null;
    public string $cpf = '';
    public string $rg = '';
    public string $telefone_whatsapp = '';

    // Endereço
    public string $endereco_completo = '';
    public string $complemento = '';
    public string $cidade = '';
    public string $estado = '';
    public string $cep = '';

    // Financeiro / bancário
    public string $salario = '';
    public string $nome_titular_conta = '';
    public string $banco = '';
    public string $agencia = '';
    public string $numero_conta = '';
    public string $tipo_conta = '';
    public string $chave_pix = '';
    public string $observacao = '';

    public function mount(?Funcionario $funcionario = null): void
    {
        if ($funcionario && $funcionario->exists) {
            $this->funcionario = $funcionario;
            $this->editando = true;
            $this->fill([
                'nome_completo' => $funcionario->nome_completo,
                'data_nascimento' => optional($funcionario->data_nascimento)->format('Y-m-d'),
                'cpf' => $funcionario->cpf ?? '',
                'rg' => $funcionario->rg ?? '',
                'telefone_whatsapp' => $funcionario->telefone_whatsapp ?? '',
                'endereco_completo' => $funcionario->endereco_completo ?? '',
                'complemento' => $funcionario->complemento ?? '',
                'cidade' => $funcionario->cidade ?? '',
                'estado' => $funcionario->estado ?? '',
                'cep' => $funcionario->cep ?? '',
                'salario' => $funcionario->salario ? Valor::br($funcionario->salario) : '',
                'nome_titular_conta' => $funcionario->nome_titular_conta ?? '',
                'banco' => $funcionario->banco ?? '',
                'agencia' => $funcionario->agencia ?? '',
                'numero_conta' => $funcionario->numero_conta ?? '',
                'tipo_conta' => $funcionario->tipo_conta ?? '',
                'chave_pix' => $funcionario->chave_pix ?? '',
                'observacao' => $funcionario->observacao ?? '',
            ]);
        }
    }

    protected function rules(): array
    {
        $id = $this->funcionario?->id;

        return [
            'nome_completo' => ['required', 'max:255'],
            'data_nascimento' => ['nullable', 'date'],
            'cpf' => ['nullable', 'digits:11', Rule::unique('funcionarios', 'cpf')->ignore($id)],
            'rg' => ['nullable', 'max:20'],
            'telefone_whatsapp' => ['nullable', 'max:20'],
            'endereco_completo' => ['nullable', 'max:255'],
            'complemento' => ['nullable', 'max:255'],
            'cidade' => ['nullable', 'max:100'],
            'estado' => ['nullable', 'size:2'],
            'cep' => ['nullable', 'digits:8'],
            'salario' => ['nullable'],
            'nome_titular_conta' => ['nullable', 'max:255'],
            'banco' => ['nullable', 'max:100'],
            'agencia' => ['nullable', 'max:20'],
            'numero_conta' => ['nullable', 'max:50'],
            'tipo_conta' => ['nullable', Rule::in(array_keys(Funcionario::TIPOS_CONTA))],
            'chave_pix' => ['nullable', 'max:255'],
            'observacao' => ['nullable'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'cpf.digits' => 'O CPF deve conter 11 dígitos.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'cep.digits' => 'O CEP deve conter 8 dígitos.',
            'estado.size' => 'Use a sigla do estado (2 letras).',
        ];
    }

    public function updatedCpf($value): void
    {
        $this->cpf = preg_replace('/\D/', '', (string) $value);
    }

    public function updatedCep($value): void
    {
        $this->cep = preg_replace('/\D/', '', (string) $value);
    }

    public function salvar()
    {
        $data = $this->validate();

        $data['cpf'] = $data['cpf'] ? preg_replace('/\D/', '', $data['cpf']) : null;
        $data['cep'] = $data['cep'] ? preg_replace('/\D/', '', $data['cep']) : null;
        $data['estado'] = $data['estado'] ? mb_strtoupper($data['estado']) : null;
        $data['salario'] = Valor::paraDecimal($this->salario);

        if ($this->editando) {
            $data['usuario_atualizacao'] = auth()->id();
            $this->funcionario->update($data);
            session()->flash('success', 'Funcionário atualizado com sucesso.');
        } else {
            $data['usuario_criacao'] = auth()->id();
            Funcionario::create($data);
            session()->flash('success', 'Funcionário cadastrado com sucesso.');
        }

        return $this->redirectRoute('funcionarios.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.funcionarios.form');
    }
}

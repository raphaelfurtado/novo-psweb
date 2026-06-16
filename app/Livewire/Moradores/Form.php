<?php

namespace App\Livewire\Moradores;

use App\Models\Endereco;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?User $user = null;
    public bool $editando = false;

    // Usuário
    public string $nome = '';
    public string $telefone = '';
    public string $telefone_2 = '';
    public string $cpf = '';
    public ?string $aniversario = null;
    public string $role = 'user';
    public string $possui_acordo = 'N';
    public string $acordo = '';
    public string $senha = '';

    // Endereço
    public string $rua = '';
    public string $numero = '';
    public ?int $quadra = null;
    public ?int $qtd_lote = 1;

    public function mount(?User $user = null): void
    {
        if ($user && $user->exists) {
            $this->user = $user->load('endereco');
            $this->editando = true;

            $this->fill([
                'nome' => $user->nome,
                'telefone' => $user->telefone,
                'telefone_2' => $user->telefone_2 ?? '',
                'cpf' => $user->cpf ?? '',
                'aniversario' => $user->aniversario ?: null,
                'role' => $user->role ?? 'user',
                'possui_acordo' => $user->possui_acordo ?: 'N',
                'acordo' => $user->acordo ?? '',
            ]);

            if ($user->endereco) {
                $this->fill([
                    'rua' => $user->endereco->rua ?? '',
                    'numero' => $user->endereco->numero ?? '',
                    'quadra' => $user->endereco->quadra,
                    'qtd_lote' => $user->endereco->qtd_lote ?? 1,
                ]);
            }
        }
    }

    protected function rules(): array
    {
        $userId = $this->user?->id;
        $enderecoId = $this->user?->endereco?->id;

        return [
            'nome' => ['required', 'max:255'],
            'telefone' => ['required', 'max:15', Rule::unique('users', 'telefone')->ignore($userId)],
            'telefone_2' => ['nullable', 'max:15'],
            'cpf' => ['nullable', 'max:14', function ($attribute, $value, $fail) {
                if ($value && strlen(preg_replace('/\D/', '', $value)) !== 11) {
                    $fail('CPF deve ter 11 dígitos.');
                }
            }],
            'aniversario' => ['nullable'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'possui_acordo' => ['required', Rule::in(['S', 'N'])],
            'acordo' => ['nullable', 'max:512'],
            'senha' => [$this->editando ? 'nullable' : 'required', 'min:6'],
            'rua' => ['nullable', 'max:50'],
            'numero' => [
                'required', 'max:5',
                Rule::unique('endereco', 'numero')
                    ->where(fn ($q) => $q->where('quadra', $this->quadra))
                    ->ignore($enderecoId),
            ],
            'quadra' => ['required', 'integer'],
            'qtd_lote' => ['nullable', 'integer'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'telefone.unique' => 'Este telefone já está cadastrado.',
            'numero.unique' => 'Já existe um morador nesta quadra e número.',
            'senha.min' => 'A senha deve ter ao menos 6 caracteres.',
        ];
    }

    public function salvar()
    {
        $this->telefone = preg_replace('/\D/', '', $this->telefone);
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            $dadosUser = [
                'nome' => $validated['nome'],
                'telefone' => preg_replace('/\D/', '', $validated['telefone']),
                'telefone_2' => $validated['telefone_2'] ?: null,
                'cpf' => $validated['cpf'] ?: null,
                'aniversario' => $validated['aniversario'] ?? '',
                'role' => $validated['role'],
                'possui_acordo' => $validated['possui_acordo'],
                'acordo' => $validated['acordo'] ?: null,
            ];

            if (! empty($this->senha)) {
                $dadosUser['senha'] = Hash::make($this->senha);
            }

            if ($this->editando) {
                $this->user->update($dadosUser);
                $user = $this->user;
            } else {
                $dadosUser['consent_policy'] = 'N';
                $user = User::create($dadosUser);
            }

            $dadosEndereco = [
                'rua' => $validated['rua'] ?: null,
                'numero' => $validated['numero'],
                'quadra' => $validated['quadra'],
                'qtd_lote' => $validated['qtd_lote'] ?? 1,
            ];

            Endereco::updateOrCreate(
                ['id_usuario' => $user->id],
                $dadosEndereco + ['data_insert' => now()]
            );
        });

        session()->flash('success', $this->editando ? 'Morador atualizado com sucesso.' : 'Morador cadastrado com sucesso.');

        return $this->redirectRoute('moradores.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.moradores.form');
    }
}

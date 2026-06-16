<?php

namespace App\Livewire\Conta;

use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class TrocarSenha extends Component
{
    public string $atual = '';
    public string $nova = '';
    public string $nova_confirmation = '';

    protected function rules(): array
    {
        return [
            'atual' => ['required'],
            'nova' => ['required', 'min:6', 'confirmed'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'nova.min' => 'A nova senha deve ter ao menos 6 caracteres.',
            'nova.confirmed' => 'A confirmação não corresponde à nova senha.',
        ];
    }

    public function salvar(): void
    {
        $this->validate();

        $user = auth()->user();

        if (! Hash::check($this->atual, $user->senha)) {
            $this->addError('atual', 'A senha atual está incorreta.');

            return;
        }

        $user->senha = Hash::make($this->nova);
        $user->save();

        $this->reset('atual', 'nova', 'nova_confirmation');
        session()->flash('success', 'Senha alterada com sucesso.');
    }

    public function render()
    {
        return view('livewire.conta.trocar-senha');
    }
}

<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    #[Validate('required', message: 'Informe o telefone.')]
    public string $telefone = '';

    #[Validate('required', message: 'Informe a senha.')]
    public string $senha = '';

    public bool $mostrarSenha = false;

    public function login()
    {
        $this->validate();

        $telefone = preg_replace('/\D/', '', $this->telefone);

        if (! Auth::attempt(['telefone' => $telefone, 'password' => $this->senha])) {
            $this->addError('telefone', 'Telefone ou senha inválidos.');

            return;
        }

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        if (Auth::user()->consent_policy === 'N') {
            return $this->redirectRoute('consent', navigate: false);
        }

        return $this->redirectRoute('dashboard', navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Consent extends Component
{
    public bool $aceito = false;

    public function aceitar()
    {
        $this->validate(
            ['aceito' => 'accepted'],
            ['aceito.accepted' => 'É preciso aceitar a política para continuar.']
        );

        $user = Auth::user();
        $user->consent_policy = 'S';
        $user->save();

        return $this->redirectRoute('dashboard', navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.consent');
    }
}

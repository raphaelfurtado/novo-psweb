<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_pagina_de_login_renderiza(): void
    {
        $this->get('/login')->assertOk()->assertSee('Acessar o sistema');
    }
}

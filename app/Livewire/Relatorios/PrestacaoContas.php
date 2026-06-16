<?php

namespace App\Livewire\Relatorios;

use App\Livewire\Concerns\ComPeriodo;
use App\Services\RelatorioService;
use Livewire\Component;

class PrestacaoContas extends Component
{
    use ComPeriodo;

    public function render(RelatorioService $service)
    {
        $r = $this->periodo();
        $dados = $service->prestacaoContas($r['inicio'], $r['fim']);

        return view('livewire.relatorios.prestacao-contas', array_merge($dados, [
            'r' => $r,
            'meses' => $this->mesesDisponiveis(),
            'saldo' => $dados['totalReceitas'] - $dados['totalDespesas'],
        ]));
    }
}

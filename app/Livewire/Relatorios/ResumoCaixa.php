<?php

namespace App\Livewire\Relatorios;

use App\Livewire\Concerns\ComPeriodo;
use App\Services\RelatorioService;
use Livewire\Component;

class ResumoCaixa extends Component
{
    use ComPeriodo;

    public function render(RelatorioService $service)
    {
        $r = $this->periodo();
        $dados = $service->resumoCaixa($r['inicio'], $r['fim']);

        return view('livewire.relatorios.resumo-caixa', array_merge($dados, [
            'r' => $r,
            'meses' => $this->mesesDisponiveis(),
            'saldo' => $dados['totalEntradas'] - $dados['totalSaidas'],
        ]));
    }
}

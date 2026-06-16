<?php

namespace App\Livewire\Relatorios;

use App\Livewire\Concerns\ComPeriodo;
use App\Services\RelatorioService;
use Livewire\Component;

class ReceitasCategoria extends Component
{
    use ComPeriodo;

    public function render(RelatorioService $service)
    {
        $r = $this->periodo();

        $agrupado = $service->receitasCategoria($r['inicio'], $r['fim']);

        return view('livewire.relatorios.receitas-categoria', [
            'r' => $r,
            'meses' => $this->mesesDisponiveis(),
            'agrupado' => $agrupado,
            'totalGeral' => (float) $agrupado->sum('total'),
            'qtdGeral' => (int) $agrupado->sum('quantidade'),
        ]);
    }
}

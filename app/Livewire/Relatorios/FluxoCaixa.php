<?php

namespace App\Livewire\Relatorios;

use App\Livewire\Concerns\ComPeriodo;
use App\Models\Pagamento;
use App\Models\Saida;
use Livewire\Component;

class FluxoCaixa extends Component
{
    use ComPeriodo;

    public function render()
    {
        $r = $this->periodo();

        $entradas = Pagamento::query()->pago()
            ->join('tipo_pagamento', 'tipo_pagamento.codigo', '=', 'pagamento.id_tipo_pagamento')
            ->noPeriodo($r['inicio'], $r['fim'], 'pagamento.referencia')
            ->selectRaw('tipo_pagamento.descricao, SUM(pagamento.valor) as total')
            ->groupBy('pagamento.id_tipo_pagamento', 'tipo_pagamento.descricao')
            ->orderByDesc('total')->get();

        $saidas = Saida::query()
            ->join('tipo_pagamento', 'tipo_pagamento.codigo', '=', 'saida.id_tipo_pagamento')
            ->noPeriodo($r['inicio'], $r['fim'], 'saida.referencia')
            ->selectRaw('tipo_pagamento.descricao, SUM(saida.valor) as total')
            ->groupBy('saida.id_tipo_pagamento', 'tipo_pagamento.descricao')
            ->orderByDesc('total')->get();

        return view('livewire.relatorios.fluxo-caixa', [
            'r' => $r,
            'meses' => $this->mesesDisponiveis(),
            'entradas' => $entradas,
            'saidas' => $saidas,
            'totalEntradas' => (float) $entradas->sum('total'),
            'totalSaidas' => (float) $saidas->sum('total'),
        ]);
    }
}

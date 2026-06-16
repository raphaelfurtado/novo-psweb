<?php

namespace App\Livewire\Relatorios;

use App\Livewire\Concerns\ComPeriodo;
use App\Models\Saida;
use Livewire\Component;

class FolhaPagamento extends Component
{
    use ComPeriodo;

    public function render()
    {
        $r = $this->periodo();

        $pagamentos = Saida::query()
            ->deFolha()
            ->join('funcionarios', 'funcionarios.id', '=', 'saida.id_funcionario')
            ->leftJoin('tipo_saida', 'tipo_saida.codigo', '=', 'saida.id_tipo_saida')
            ->noPeriodo($r['inicio'], $r['fim'], 'saida.referencia')
            ->selectRaw('funcionarios.nome_completo, saida.valor, saida.data_pagamento,
                saida.observacao, tipo_saida.descricao as tipo')
            ->orderBy('funcionarios.nome_completo')->orderBy('saida.data_pagamento')
            ->get();

        return view('livewire.relatorios.folha-pagamento', [
            'r' => $r,
            'meses' => $this->mesesDisponiveis(),
            'pagamentos' => $pagamentos,
            'total' => (float) $pagamentos->sum('valor'),
        ]);
    }
}

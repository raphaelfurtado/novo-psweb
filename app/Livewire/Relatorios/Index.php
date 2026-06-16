<?php

namespace App\Livewire\Relatorios;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $relatorios = [
            ['rota' => 'relatorios.prestacao-contas', 'titulo' => 'Prestação de Contas', 'desc' => 'Receitas e despesas detalhadas, com PDF.', 'cor' => 'brand'],
            ['rota' => 'relatorios.resumo-caixa', 'titulo' => 'Resumo de Caixa', 'desc' => 'Entradas consolidadas e saídas detalhadas.', 'cor' => 'green'],
            ['rota' => 'relatorios.fluxo-caixa', 'titulo' => 'Fluxo de Caixa', 'desc' => 'Entradas × saídas por categoria e saldo.', 'cor' => 'brand'],
            ['rota' => 'relatorios.receitas-categoria', 'titulo' => 'Receitas por Categoria', 'desc' => 'Total e quantidade por tipo de receita.', 'cor' => 'green'],
            ['rota' => 'relatorios.inadimplencia', 'titulo' => 'Inadimplência', 'desc' => 'Pagamentos em aberto por período e quadra.', 'cor' => 'amber'],
            ['rota' => 'relatorios.inadimplencia-avancada', 'titulo' => 'Inadimplência Avançada', 'desc' => 'Ranking de devedores e meses em atraso.', 'cor' => 'red'],
            ['rota' => 'relatorios.folha-pagamento', 'titulo' => 'Folha de Pagamento', 'desc' => 'Pagamentos a funcionários no período.', 'cor' => 'red'],
            ['rota' => 'relatorios.extrato-morador', 'titulo' => 'Extrato do Morador', 'desc' => 'Todos os pagamentos de um morador, com PDF.', 'cor' => 'brand'],
            ['rota' => 'relatorios.demonstrativo-anual', 'titulo' => 'Demonstrativo Anual', 'desc' => '12 meses: receita, despesa e saldo acumulado.', 'cor' => 'green'],
        ];

        return view('livewire.relatorios.index', compact('relatorios'));
    }
}

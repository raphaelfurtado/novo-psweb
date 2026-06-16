<?php

namespace App\Services;

use App\Models\Pagamento;
use App\Models\Saida;
use Illuminate\Support\Collection;

/**
 * Fonte única das consultas dos relatórios — reutilizada pelos componentes
 * Livewire e pelo controller de PDF/Excel, garantindo números idênticos.
 *
 * Espelha as queries do Relatorio.php do psweb (situação PAGO nas receitas,
 * saídas sem filtro de situação, junção tipo_pagamento por `codigo`).
 */
class RelatorioService
{
    public function receitasCategoria(string $inicio, string $fim): Collection
    {
        return Pagamento::query()->pago()
            ->join('tipo_pagamento', 'tipo_pagamento.codigo', '=', 'pagamento.id_tipo_pagamento')
            ->noPeriodo($inicio, $fim, 'pagamento.referencia')
            ->selectRaw('tipo_pagamento.codigo, tipo_pagamento.descricao,
                COUNT(pagamento.id) as quantidade, SUM(pagamento.valor) as total')
            ->groupBy('tipo_pagamento.codigo', 'tipo_pagamento.descricao')
            ->orderByDesc('total')->get();
    }

    public function receitasDetalhadas(string $inicio, string $fim): Collection
    {
        return Pagamento::query()->pago()
            ->join('users', 'users.id', '=', 'pagamento.id_usuario')
            ->join('endereco', 'endereco.id_usuario', '=', 'users.id')
            ->join('tipo_pagamento', 'tipo_pagamento.codigo', '=', 'pagamento.id_tipo_pagamento')
            ->noPeriodo($inicio, $fim, 'pagamento.referencia')
            ->selectRaw('users.nome, endereco.quadra, endereco.numero, pagamento.valor,
                pagamento.data_pagamento, pagamento.observacao, tipo_pagamento.descricao as categoria')
            ->orderBy('pagamento.data_pagamento')->get();
    }

    public function despesasDetalhadas(string $inicio, string $fim): Collection
    {
        return Saida::query()
            ->join('tipo_pagamento', 'tipo_pagamento.codigo', '=', 'saida.id_tipo_pagamento')
            ->leftJoin('funcionarios', 'funcionarios.id', '=', 'saida.id_funcionario')
            ->leftJoin('recebedor', 'recebedor.id', '=', 'saida.id_recebedor')
            ->noPeriodo($inicio, $fim, 'saida.referencia')
            ->selectRaw('saida.valor, saida.data_pagamento, saida.observacao,
                tipo_pagamento.descricao as categoria,
                COALESCE(funcionarios.nome_completo, recebedor.nome, "Não especificado") as favorecido')
            ->orderBy('saida.data_pagamento')->get();
    }

    public function entradasPorTipo(string $inicio, string $fim): Collection
    {
        return Pagamento::query()->pago()
            ->join('tipo_pagamento', 'tipo_pagamento.codigo', '=', 'pagamento.id_tipo_pagamento')
            ->noPeriodo($inicio, $fim, 'pagamento.referencia')
            ->selectRaw('tipo_pagamento.codigo, tipo_pagamento.descricao, SUM(pagamento.valor) as total')
            ->groupBy('tipo_pagamento.codigo', 'tipo_pagamento.descricao')
            ->orderByDesc('total')->get();
    }

    public function saidasPorTipo(string $inicio, string $fim): Collection
    {
        return Saida::query()
            ->join('tipo_pagamento', 'tipo_pagamento.codigo', '=', 'saida.id_tipo_pagamento')
            ->noPeriodo($inicio, $fim, 'saida.referencia')
            ->selectRaw('tipo_pagamento.codigo, tipo_pagamento.descricao, SUM(saida.valor) as total')
            ->groupBy('tipo_pagamento.codigo', 'tipo_pagamento.descricao')
            ->orderByDesc('total')->get();
    }

    public function saidasDetalhadas(string $inicio, string $fim): Collection
    {
        return Saida::query()
            ->join('tipo_pagamento', 'tipo_pagamento.codigo', '=', 'saida.id_tipo_pagamento')
            ->noPeriodo($inicio, $fim, 'saida.referencia')
            ->selectRaw('saida.valor, saida.data_pagamento, saida.observacao, tipo_pagamento.descricao as categoria')
            ->orderBy('saida.data_pagamento')->get();
    }

    /** Dados completos da Prestação de Contas. */
    public function prestacaoContas(string $inicio, string $fim): array
    {
        $receitas = $this->receitasDetalhadas($inicio, $fim);
        $despesas = $this->despesasDetalhadas($inicio, $fim);

        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'totalReceitas' => (float) $receitas->sum('valor'),
            'totalDespesas' => (float) $despesas->sum('valor'),
        ];
    }

    /** Dados completos do Resumo de Caixa. */
    public function resumoCaixa(string $inicio, string $fim): array
    {
        $entradas = $this->entradasPorTipo($inicio, $fim);
        $saidasDetalhe = $this->saidasDetalhadas($inicio, $fim);

        return [
            'entradas' => $entradas,
            'saidas' => $this->saidasPorTipo($inicio, $fim),
            'saidasDetalhe' => $saidasDetalhe,
            'totalEntradas' => (float) $entradas->sum('total'),
            'totalSaidas' => (float) $saidasDetalhe->sum('valor'),
        ];
    }
}

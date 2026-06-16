@extends('pdf.layout')
@section('titulo', 'Resumo de Caixa')

@section('conteudo')
    <table class="cards">
        <tr>
            <td width="33%"><div class="card"><div class="lbl">Total Arrecadado</div><div class="val green">{{ \App\Support\Valor::moeda($totalEntradas) }}</div></div></td>
            <td width="33%"><div class="card"><div class="lbl">Total de Despesas</div><div class="val red">{{ \App\Support\Valor::moeda($totalSaidas) }}</div></div></td>
            <td width="33%"><div class="card"><div class="lbl">Saldo em Caixa</div><div class="val blue">{{ \App\Support\Valor::moeda($totalEntradas - $totalSaidas) }}</div></div></td>
        </tr>
    </table>

    <div class="section-title">Receitas consolidadas</div>
    <table>
        <thead><tr><th>Categoria</th><th class="right">Total</th></tr></thead>
        <tbody>
            @forelse ($entradas as $e)
                <tr><td>{{ $e->descricao }}</td><td class="right green">{{ \App\Support\Valor::br($e->total) }}</td></tr>
            @empty
                <tr><td colspan="2" class="center">Sem receitas.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td>Total</td><td class="right green">{{ \App\Support\Valor::moeda($totalEntradas) }}</td></tr></tfoot>
    </table>

    <div class="section-title">Detalhamento de saídas</div>
    <table>
        <thead><tr><th>Data</th><th>Categoria</th><th>Observação</th><th class="right">Valor</th></tr></thead>
        <tbody>
            @forelse ($saidasDetalhe as $s)
                <tr>
                    <td>{{ optional($s->data_pagamento ? \Illuminate\Support\Carbon::parse($s->data_pagamento) : null)->format('d/m/Y') ?? '—' }}</td>
                    <td><span class="badge">{{ $s->categoria }}</span></td>
                    <td>{{ $s->observacao ?: '—' }}</td>
                    <td class="right red">{{ \App\Support\Valor::br($s->valor) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="center">Sem saídas.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="3">Total de saídas</td><td class="right red">{{ \App\Support\Valor::moeda($totalSaidas) }}</td></tr></tfoot>
    </table>
@endsection

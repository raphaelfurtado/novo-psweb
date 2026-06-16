@extends('pdf.layout')
@section('titulo', 'Prestação de Contas')

@section('conteudo')
    <table class="cards">
        <tr>
            <td width="33%"><div class="card"><div class="lbl">Total de Entradas</div><div class="val green">{{ \App\Support\Valor::moeda($totalReceitas) }}</div></div></td>
            <td width="33%"><div class="card"><div class="lbl">Total de Saídas</div><div class="val red">{{ \App\Support\Valor::moeda($totalDespesas) }}</div></div></td>
            <td width="33%"><div class="card"><div class="lbl">Saldo Líquido</div><div class="val blue">{{ \App\Support\Valor::moeda($totalReceitas - $totalDespesas) }}</div></div></td>
        </tr>
    </table>

    <div class="section-title">Despesas detalhadas</div>
    <table>
        <thead><tr><th>Data</th><th>Favorecido</th><th>Categoria</th><th>Descrição</th><th class="right">Valor</th></tr></thead>
        <tbody>
            @forelse ($despesas as $d)
                <tr>
                    <td>{{ optional($d->data_pagamento ? \Illuminate\Support\Carbon::parse($d->data_pagamento) : null)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $d->favorecido }}</td>
                    <td><span class="badge">{{ $d->categoria }}</span></td>
                    <td>{{ $d->observacao ?: '—' }}</td>
                    <td class="right red">{{ \App\Support\Valor::br($d->valor) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center">Sem despesas no período.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="4">Total de despesas</td><td class="right red">{{ \App\Support\Valor::moeda($totalDespesas) }}</td></tr></tfoot>
    </table>

    <div class="section-title">Receitas detalhadas</div>
    <table>
        <thead><tr><th>Data</th><th>Morador</th><th>Q/Casa</th><th>Categoria</th><th class="right">Valor</th></tr></thead>
        <tbody>
            @forelse ($receitas as $rec)
                <tr>
                    <td>{{ optional($rec->data_pagamento ? \Illuminate\Support\Carbon::parse($rec->data_pagamento) : null)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $rec->nome }}</td>
                    <td>Q{{ $rec->quadra }}·{{ $rec->numero }}</td>
                    <td><span class="badge">{{ $rec->categoria }}</span></td>
                    <td class="right green">{{ \App\Support\Valor::br($rec->valor) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center">Sem receitas no período.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="4">Total de receitas</td><td class="right green">{{ \App\Support\Valor::moeda($totalReceitas) }}</td></tr></tfoot>
    </table>
@endsection

@extends('pdf.layout')
@section('titulo', 'Saídas / Despesas')

@section('conteudo')
    @if ($truncado)
        <div style="margin-bottom:8px;color:#b45309;font-size:10px">
            Exibindo as primeiras {{ $max }} de {{ $count }} linhas. Use o Excel para a lista completa.
        </div>
    @endif
    <table>
        <thead>
            <tr><th>Data</th><th>Categoria</th><th>Recebedor</th><th>Observação</th><th>Ref.</th><th class="right">Valor</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $s)
                <tr>
                    <td>{{ optional($s->data_pagamento)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $s->tipoPagamento?->descricao ?? '—' }}</td>
                    <td>{{ $s->recebedor?->nome ?? '—' }}</td>
                    <td>{{ $s->observacao ?: '—' }}</td>
                    <td>{{ \App\Support\Referencia::abreviada($s->referencia) }}</td>
                    <td class="right red">{{ \App\Support\Valor::br($s->valor) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center">Nenhuma despesa para os filtros.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total · {{ $count }} despesa(s)</td>
                <td class="right red">{{ \App\Support\Valor::moeda($total) }}</td>
            </tr>
        </tfoot>
    </table>
@endsection

@extends('pdf.layout')
@section('titulo', 'Folha de Pagamento')

@section('conteudo')
    @if ($truncado)
        <div style="margin-bottom:8px;color:#b45309;font-size:10px">
            Exibindo as primeiras {{ $max }} de {{ $count }} linhas. Use o Excel para a lista completa.
        </div>
    @endif
    <table>
        <thead>
            <tr><th>Funcionário</th><th>Tipo</th><th>Data</th><th>Ref.</th><th>Observação</th><th class="right">Valor</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $p)
                <tr>
                    <td>{{ $p->funcionario?->nome_completo ?? '—' }}</td>
                    <td>{{ $p->tipoSaida?->descricao ?? '—' }}</td>
                    <td>{{ optional($p->data_pagamento)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ \App\Support\Referencia::abreviada($p->referencia) }}</td>
                    <td>{{ $p->observacao ?: '—' }}</td>
                    <td class="right red">{{ \App\Support\Valor::br($p->valor) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center">Nenhum pagamento para os filtros.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total · {{ $count }} pagamento(s)</td>
                <td class="right red">{{ \App\Support\Valor::moeda($total) }}</td>
            </tr>
        </tfoot>
    </table>
@endsection

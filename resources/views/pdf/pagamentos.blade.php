@extends('pdf.layout')
@section('titulo', 'Pagamentos')

@section('conteudo')
    @if ($truncado)
        <div style="margin-bottom:8px;color:#b45309;font-size:10px">
            Exibindo as primeiras {{ $max }} de {{ $count }} linhas. Use o Excel para a lista completa.
        </div>
    @endif
    <table>
        <thead>
            <tr>
                <th>Morador</th><th>Qd.</th><th>Casa</th><th>Ref.</th><th>Tipo</th><th>Forma</th>
                <th>Pagamento</th><th>Vencimento</th><th class="right">Valor</th><th>Situação</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $p)
                <tr>
                    <td>{{ $p->usuario?->nome ?? '—' }}</td>
                    <td>{{ $p->usuario?->endereco?->quadra ?? '—' }}</td>
                    <td>{{ $p->usuario?->endereco?->numero ?? '—' }}</td>
                    <td>{{ \App\Support\Referencia::abreviada($p->referencia) }}</td>
                    <td>{{ $p->tipoPagamento?->descricao ?? '—' }}</td>
                    <td>{{ $p->formaPagamento?->descricao ?? '—' }}</td>
                    <td>{{ optional($p->data_pagamento)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ optional($p->data_vencimento)->format('d/m/Y') ?? '—' }}</td>
                    <td class="right">{{ \App\Support\Valor::br($p->valor) }}</td>
                    <td><span class="badge">{{ $p->esta_vencido ? 'VENCIDO' : $p->situacao }}</span></td>
                </tr>
            @empty
                <tr><td colspan="10" class="center">Nenhum pagamento para os filtros.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8">Total · {{ $count }} lançamento(s)</td>
                <td class="right">{{ \App\Support\Valor::moeda($total) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
@endsection

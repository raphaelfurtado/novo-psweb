@extends('pdf.layout')
@section('titulo', 'Extrato do Morador')

@section('conteudo')
    <table class="cards">
        <tr>
            <td width="60%">
                <div class="card">
                    <div class="val" style="font-size:14px">{{ $morador->nome }}</div>
                    <div class="lbl">
                        {{ $morador->telefone }}
                        @if ($morador->endereco) · Quadra {{ $morador->endereco->quadra }}, nº {{ $morador->endereco->numero }}@endif
                    </div>
                </div>
            </td>
            <td width="20%"><div class="card"><div class="lbl">Pago</div><div class="val green">{{ \App\Support\Valor::br($totalPago) }}</div></div></td>
            <td width="20%"><div class="card"><div class="lbl">Em aberto</div><div class="val red">{{ \App\Support\Valor::br($totalAberto) }}</div></div></td>
        </tr>
    </table>

    <table>
        <thead><tr><th>Referência</th><th>Tipo</th><th>Forma</th><th>Vencimento</th><th>Pagamento</th><th class="right">Valor</th><th>Situação</th></tr></thead>
        <tbody>
            @forelse ($pagamentos as $p)
                <tr>
                    <td>{{ \App\Support\Referencia::abreviada($p->referencia) }}</td>
                    <td>{{ $p->tipoPagamento?->descricao ?? '—' }}</td>
                    <td>{{ $p->formaPagamento?->descricao ?? '—' }}</td>
                    <td>{{ optional($p->data_vencimento)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ optional($p->data_pagamento)->format('d/m/Y') ?? '—' }}</td>
                    <td class="right">{{ \App\Support\Valor::br($p->valor) }}</td>
                    <td><span class="badge">{{ $p->situacao }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">Sem pagamentos no período.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

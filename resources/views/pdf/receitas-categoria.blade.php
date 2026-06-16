@extends('pdf.layout')
@section('titulo', 'Receitas por Categoria')

@section('conteudo')
    <table>
        <thead><tr><th>Cód.</th><th>Categoria</th><th class="right">Lançamentos</th><th class="right">Total</th></tr></thead>
        <tbody>
            @forelse ($agrupado as $a)
                <tr>
                    <td><span class="badge">{{ $a->codigo }}</span></td>
                    <td>{{ $a->descricao }}</td>
                    <td class="right">{{ $a->quantidade }}</td>
                    <td class="right green">{{ \App\Support\Valor::br($a->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="center">Sem receitas no período.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="2">Totais consolidados</td><td class="right">{{ $qtdGeral }}</td><td class="right green">{{ \App\Support\Valor::moeda($totalGeral) }}</td></tr>
        </tfoot>
    </table>
@endsection

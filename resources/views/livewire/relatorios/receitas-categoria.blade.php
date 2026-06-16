<div>
    <x-page-header title="Receitas por Categoria" :subtitle="$r['label']">
        <x-slot:actions>
            <a href="{{ route('relatorios.index') }}" wire:navigate class="btn-secondary no-print">Relatórios</a>
            <a href="{{ route('relatorios.pdf', $this->paramsPeriodo(['relatorio' => 'receitas-categoria'])) }}" target="_blank" class="btn-primary no-print">Exportar PDF</a>
            <a href="{{ route('relatorios.excel', $this->paramsPeriodo(['relatorio' => 'receitas-categoria'])) }}" class="btn-success no-print">Excel</a>
        </x-slot:actions>
    </x-page-header>

    <x-filtro-periodo :meses="$meses" :modoPeriodo="$modoPeriodo" />

    <x-card :padding="false">
        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr><th class="w-20">Cód.</th><th>Categoria</th><th class="text-right">Lançamentos</th><th class="text-right">Total</th></tr>
                </thead>
                <tbody>
                    @forelse ($agrupado as $a)
                        <tr wire:key="rc-{{ $a->codigo }}">
                            <td><x-badge color="blue">{{ $a->codigo }}</x-badge></td>
                            <td class="font-medium text-gray-900 dark:text-white">{{ $a->descricao }}</td>
                            <td class="text-right text-gray-500">{{ $a->quantidade }}</td>
                            <td class="text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::br($a->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-gray-400">Sem receitas no período.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                    <tr>
                        <td colspan="2" class="px-4 py-3 font-semibold">Totais consolidados</td>
                        <td class="px-4 py-3 text-right font-bold">{{ $qtdGeral }}</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::moeda($totalGeral) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</div>

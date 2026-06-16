<div>
    <x-page-header title="Prestação de Contas" :subtitle="$r['label']">
        <x-slot:actions>
            <a href="{{ route('relatorios.index') }}" wire:navigate class="btn-secondary no-print">Relatórios</a>
            <a href="{{ route('relatorios.pdf', $this->paramsPeriodo(['relatorio' => 'prestacao-contas'])) }}" target="_blank" class="btn-primary no-print">Exportar PDF</a>
            <a href="{{ route('relatorios.excel', $this->paramsPeriodo(['relatorio' => 'prestacao-contas'])) }}" class="btn-success no-print">Excel</a>
        </x-slot:actions>
    </x-page-header>

    <x-filtro-periodo :meses="$meses" :modoPeriodo="$modoPeriodo" />

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Total de Entradas" :value="\App\Support\Valor::moeda($totalReceitas)" color="green" />
        <x-stat label="Total de Saídas" :value="\App\Support\Valor::moeda($totalDespesas)" color="red" />
        <x-stat label="Saldo Líquido" :value="\App\Support\Valor::moeda($saldo)" :color="$saldo >= 0 ? 'brand' : 'red'" />
    </div>

    <x-card title="Despesas detalhadas" :padding="false" class="mb-6">
        <div class="table-wrap border-0">
            <table class="table-base">
                <thead><tr><th>Data</th><th>Favorecido</th><th>Categoria</th><th>Descrição</th><th class="text-right">Valor</th></tr></thead>
                <tbody>
                    @forelse ($despesas as $d)
                        <tr wire:key="desp-{{ $loop->index }}">
                            <td class="text-gray-500">{{ optional($d->data_pagamento ? \Illuminate\Support\Carbon::parse($d->data_pagamento) : null)->format('d/m/Y') ?? '—' }}</td>
                            <td class="font-medium text-gray-900 dark:text-white">{{ $d->favorecido }}</td>
                            <td><x-badge color="red">{{ $d->categoria }}</x-badge></td>
                            <td class="text-gray-500">{{ $d->observacao ?: '—' }}</td>
                            <td class="text-right font-semibold text-red-600 dark:text-red-400">{{ \App\Support\Valor::br($d->valor) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-gray-400">Sem despesas no período.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                    <tr><td colspan="4" class="px-4 py-3 font-semibold">Total de despesas</td><td class="px-4 py-3 text-right font-bold text-red-600 dark:text-red-400">{{ \App\Support\Valor::moeda($totalDespesas) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </x-card>

    <x-card title="Receitas detalhadas" :padding="false">
        <div class="table-wrap border-0">
            <table class="table-base">
                <thead><tr><th>Data</th><th>Morador</th><th>Quadra/Casa</th><th>Categoria</th><th>Descrição</th><th class="text-right">Valor</th></tr></thead>
                <tbody>
                    @forelse ($receitas as $rec)
                        <tr wire:key="rec-{{ $loop->index }}">
                            <td class="text-gray-500">{{ optional($rec->data_pagamento ? \Illuminate\Support\Carbon::parse($rec->data_pagamento) : null)->format('d/m/Y') ?? '—' }}</td>
                            <td class="font-medium text-gray-900 dark:text-white">{{ $rec->nome }}</td>
                            <td class="text-gray-500">Q{{ $rec->quadra }} · {{ $rec->numero }}</td>
                            <td><x-badge color="green">{{ $rec->categoria }}</x-badge></td>
                            <td class="text-gray-500">{{ $rec->observacao ?: '—' }}</td>
                            <td class="text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::br($rec->valor) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-gray-400">Sem receitas no período.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                    <tr><td colspan="5" class="px-4 py-3 font-semibold">Total de receitas</td><td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::moeda($totalReceitas) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</div>

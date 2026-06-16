<div>
    <x-page-header title="Resumo de Caixa" :subtitle="$r['label']">
        <x-slot:actions>
            <a href="{{ route('relatorios.index') }}" wire:navigate class="btn-secondary no-print">Relatórios</a>
            <a href="{{ route('relatorios.pdf', $this->paramsPeriodo(['relatorio' => 'resumo-caixa'])) }}" target="_blank" class="btn-primary no-print">Exportar PDF</a>
            <a href="{{ route('relatorios.excel', $this->paramsPeriodo(['relatorio' => 'resumo-caixa'])) }}" class="btn-success no-print">Excel</a>
        </x-slot:actions>
    </x-page-header>

    <x-filtro-periodo :meses="$meses" :modoPeriodo="$modoPeriodo" />

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Total Arrecadado" :value="\App\Support\Valor::moeda($totalEntradas)" color="green" />
        <x-stat label="Total de Despesas" :value="\App\Support\Valor::moeda($totalSaidas)" color="red" />
        <x-stat label="Saldo em Caixa" :value="\App\Support\Valor::moeda($saldo)" :color="$saldo >= 0 ? 'brand' : 'red'" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <x-card title="Receitas consolidadas" :padding="false" class="lg:col-span-2">
            <div class="table-wrap border-0">
                <table class="table-base">
                    <thead><tr><th>Categoria</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @forelse ($entradas as $e)
                            <tr><td>{{ $e->descricao }}</td><td class="text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::br($e->total) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="py-8 text-center text-gray-400">Sem receitas.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                        <tr><td class="px-4 py-3 font-semibold">Total</td><td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::moeda($totalEntradas) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </x-card>

        <x-card title="Detalhamento de saídas" :padding="false" class="lg:col-span-3">
            <div class="table-wrap border-0">
                <table class="table-base">
                    <thead><tr><th>Data</th><th>Categoria</th><th>Observação</th><th class="text-right">Valor</th></tr></thead>
                    <tbody>
                        @forelse ($saidasDetalhe as $s)
                            <tr wire:key="rs-{{ $loop->index }}">
                                <td class="text-gray-500">{{ optional($s->data_pagamento ? \Illuminate\Support\Carbon::parse($s->data_pagamento) : null)->format('d/m/Y') ?? '—' }}</td>
                                <td><x-badge color="red">{{ $s->categoria }}</x-badge></td>
                                <td class="text-gray-500">{{ $s->observacao ?: '—' }}</td>
                                <td class="text-right font-semibold text-red-600 dark:text-red-400">{{ \App\Support\Valor::br($s->valor) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-gray-400">Sem saídas.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                        <tr><td colspan="3" class="px-4 py-3 font-semibold">Total de saídas</td><td class="px-4 py-3 text-right font-bold text-red-600 dark:text-red-400">{{ \App\Support\Valor::moeda($totalSaidas) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
    </div>
</div>

<div>
    <x-page-header title="Fluxo de Caixa" :subtitle="$r['label']">
        <x-slot:actions>
            <a href="{{ route('relatorios.index') }}" wire:navigate class="btn-secondary no-print">Relatórios</a>
            <button onclick="window.print()" class="btn-secondary no-print">Imprimir</button>
        </x-slot:actions>
    </x-page-header>

    <x-filtro-periodo :meses="$meses" :modoPeriodo="$modoPeriodo" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Entradas (receitas)" :padding="false">
            <div class="table-wrap border-0">
                <table class="table-base">
                    <thead><tr><th>Categoria</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @forelse ($entradas as $e)
                            <tr><td>{{ $e->descricao }}</td><td class="text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::br($e->total) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="py-8 text-center text-gray-400">Sem entradas no período.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                        <tr><td class="px-4 py-3 font-semibold">Total de entradas</td><td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::moeda($totalEntradas) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </x-card>

        <x-card title="Saídas (despesas)" :padding="false">
            <div class="table-wrap border-0">
                <table class="table-base">
                    <thead><tr><th>Categoria</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @forelse ($saidas as $s)
                            <tr><td>{{ $s->descricao }}</td><td class="text-right font-semibold text-red-600 dark:text-red-400">{{ \App\Support\Valor::br($s->total) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="py-8 text-center text-gray-400">Sem saídas no período.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                        <tr><td class="px-4 py-3 font-semibold">Total de saídas</td><td class="px-4 py-3 text-right font-bold text-red-600 dark:text-red-400">{{ \App\Support\Valor::moeda($totalSaidas) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
    </div>

    @php $saldo = $totalEntradas - $totalSaidas; @endphp
    <div class="mt-6 rounded-xl p-5 text-center text-lg font-bold {{ $saldo >= 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300' }}">
        Saldo líquido do período: {{ \App\Support\Valor::moeda($saldo) }}
    </div>
</div>

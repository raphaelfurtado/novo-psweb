<div>
    <x-page-header title="Folha de Pagamento" :subtitle="$r['label']">
        <x-slot:actions>
            <a href="{{ route('relatorios.index') }}" wire:navigate class="btn-secondary no-print">Relatórios</a>
            <button onclick="window.print()" class="btn-secondary no-print">Imprimir</button>
        </x-slot:actions>
    </x-page-header>

    <x-filtro-periodo :meses="$meses" :modoPeriodo="$modoPeriodo" />

    <div class="mb-4">
        <x-stat label="Total da folha no período" :value="\App\Support\Valor::moeda($total)" color="red"
                :sub="$pagamentos->count().' pagamento(s)'" />
    </div>

    <x-card :padding="false">
        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr><th>Funcionário</th><th>Tipo</th><th>Data</th><th>Observação</th><th class="text-right">Valor</th></tr>
                </thead>
                <tbody>
                    @forelse ($pagamentos as $p)
                        <tr wire:key="fp-{{ $loop->index }}">
                            <td class="font-medium text-gray-900 dark:text-white">{{ $p->nome_completo }}</td>
                            <td>{{ $p->tipo ?? '—' }}</td>
                            <td class="text-gray-500">{{ optional($p->data_pagamento ? \Illuminate\Support\Carbon::parse($p->data_pagamento) : null)->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-gray-500">{{ $p->observacao ?: '—' }}</td>
                            <td class="text-right font-semibold">{{ \App\Support\Valor::br($p->valor) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-gray-400">Sem pagamentos de folha no período.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                    <tr><td colspan="4" class="px-4 py-3 font-semibold">Total</td><td class="px-4 py-3 text-right font-bold">{{ \App\Support\Valor::moeda($total) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</div>

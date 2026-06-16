<div>
    <x-page-header title="Demonstrativo Anual" :subtitle="'Exercício de '.$ano">
        <x-slot:actions>
            <select wire:model.live="ano" class="form-select w-auto no-print">
                @foreach ($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
            </select>
            <a href="{{ route('relatorios.excel', ['relatorio' => 'demonstrativo-anual', 'ano' => $ano]) }}" class="btn-success no-print">Excel</a>
            <button onclick="window.print()" class="btn-secondary no-print">Imprimir</button>
        </x-slot:actions>
    </x-page-header>

    @php $saldoFinal = $totalEntradas - $totalSaidas; @endphp
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Entradas no ano" :value="\App\Support\Valor::moeda($totalEntradas)" color="green" />
        <x-stat label="Saídas no ano" :value="\App\Support\Valor::moeda($totalSaidas)" color="red" />
        <x-stat label="Saldo do exercício" :value="\App\Support\Valor::moeda($saldoFinal)" :color="$saldoFinal >= 0 ? 'brand' : 'red'" />
    </div>

    <x-card title="Evolução mensal" class="mb-6">
        <x-chart wire:key="demo-{{ $ano }}" :options="$chart" />
    </x-card>

    <x-card :padding="false">
        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr><th>Mês</th><th class="text-right">Entradas</th><th class="text-right">Saídas</th><th class="text-right">Saldo do mês</th><th class="text-right">Acumulado</th></tr>
                </thead>
                <tbody>
                    @foreach ($linhas as $l)
                        <tr wire:key="dem-{{ $loop->index }}">
                            <td class="font-medium text-gray-900 dark:text-white">{{ $l['mes'] }}</td>
                            <td class="text-right text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::br($l['entrada']) }}</td>
                            <td class="text-right text-red-600 dark:text-red-400">{{ \App\Support\Valor::br($l['saida']) }}</td>
                            <td class="text-right font-medium {{ $l['saldo'] >= 0 ? 'text-gray-700 dark:text-gray-200' : 'text-red-600' }}">{{ \App\Support\Valor::br($l['saldo']) }}</td>
                            <td class="text-right font-semibold {{ $l['acumulado'] >= 0 ? 'text-brand-600 dark:text-brand-400' : 'text-red-600' }}">{{ \App\Support\Valor::br($l['acumulado']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                    <tr>
                        <td class="px-4 py-3 font-semibold">Total do ano</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::br($totalEntradas) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-red-600 dark:text-red-400">{{ \App\Support\Valor::br($totalSaidas) }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ \App\Support\Valor::br($saldoFinal) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</div>

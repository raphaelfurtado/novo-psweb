<div>
    <x-page-header title="Dashboard" subtitle="Visão geral financeira do condomínio">
        <x-slot:actions>
            <select wire:model.live="ano" class="form-select w-auto">
                @foreach ($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
            </select>
        </x-slot:actions>
    </x-page-header>

    {{-- Indicadores do ano --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Entradas no ano" :value="\App\Support\Valor::moeda($totalEntradasAno)" color="green" />
        <x-stat label="Saídas no ano" :value="\App\Support\Valor::moeda($totalSaidasAno)" color="red" />
        <x-stat label="Saldo do ano" :value="\App\Support\Valor::moeda($saldoAno)" :color="$saldoAno >= 0 ? 'brand' : 'red'" />
        <x-stat label="Inadimplência (mensalidade)" :value="number_format($percInadimplencia, 1, ',', '.').'%'" color="amber"
                :sub="\App\Support\Valor::moeda($mensAberto).' em aberto'" />
    </div>

    {{-- Gráficos --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card title="Entradas × Saídas por mês" class="lg:col-span-2">
            <x-chart wire:key="fluxo-{{ $ano }}" :options="$chartFluxo" />
        </x-card>
        <x-card title="Receita por categoria">
            @if (collect($chartCategoria['series'])->sum() > 0)
                <x-chart wire:key="cat-{{ $ano }}" :options="$chartCategoria" />
            @else
                <p class="py-10 text-center text-sm text-gray-400">Sem receitas no período.</p>
            @endif
        </x-card>
    </div>

    <div class="mb-6">
        <x-card title="Evolução do saldo acumulado">
            <x-chart wire:key="saldo-{{ $ano }}" :options="$chartSaldo" />
        </x-card>
    </div>

    {{-- Resumo de caixa por referência --}}
    <div class="mb-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Resumo de Caixa</h2>
            <select wire:model.live="ref" class="form-select w-auto">
                @foreach ($referencias as $cod => $label)<option value="{{ $cod }}">{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-card title="Mensalidade / Geral" subtitle="Entradas (mensalidade, concreto e roçagem)">
                <div class="grid grid-cols-3 gap-4">
                    <x-stat label="Entrada" :value="\App\Support\Valor::moeda($caixa['entrada'])" color="green" />
                    <x-stat label="Saída" :value="\App\Support\Valor::moeda($caixa['saida'])" color="red" />
                    <x-stat label="Em caixa" :value="\App\Support\Valor::moeda($caixa['saldo'])" :color="$caixa['saldo'] >= 0 ? 'brand' : 'red'" />
                </div>
            </x-card>
            <x-card title="Concreto" subtitle="Caixa segregado do concreto">
                <div class="grid grid-cols-3 gap-4">
                    <x-stat label="Entrada" :value="\App\Support\Valor::moeda($caixaConcreto['entrada'])" color="green" />
                    <x-stat label="Saída" :value="\App\Support\Valor::moeda($caixaConcreto['saida'])" color="red" />
                    <x-stat label="Em caixa" :value="\App\Support\Valor::moeda($caixaConcreto['saldo'])" :color="$caixaConcreto['saldo'] >= 0 ? 'brand' : 'red'" />
                </div>
            </x-card>
        </div>
    </div>

    {{-- KPIs por categoria --}}
    <x-card title="Arrecadação por categoria em {{ $ano }}" :padding="false">
        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th class="text-right">Pago</th>
                        <th class="text-right">Em aberto</th>
                        <th class="text-right">Lançamentos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kpis as $k)
                        <tr wire:key="kpi-{{ $k->codigo }}">
                            <td class="font-medium text-gray-900 dark:text-white">{{ $k->descricao }}</td>
                            <td class="text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Valor::br($k->pago) }}</td>
                            <td class="text-right text-amber-600 dark:text-amber-400">{{ \App\Support\Valor::br($k->aberto) }}</td>
                            <td class="text-right text-gray-500">{{ $k->qtd_pago }}/{{ $k->qtd }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-gray-400">Sem lançamentos em {{ $ano }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

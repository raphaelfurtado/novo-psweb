<div>
    <x-page-header :title="'Bem-vindo, '.auth()->user()->primeiro_nome" subtitle="Transparência — contribuições do condomínio">
        <x-slot:actions>
            <label class="flex items-center gap-2 text-sm text-gray-500">
                Ano:
                <select wire:model.live="ano" class="form-select w-auto">
                    @foreach ($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                </select>
            </label>
        </x-slot:actions>
    </x-page-header>

    @if (empty($meses))
        <x-card>
            <p class="py-10 text-center text-gray-400">Sem contribuições registradas em {{ $ano }}.</p>
        </x-card>
    @else
        @php
            $icon = '<svg class="h-9 w-9 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>';
        @endphp

        <x-card :padding="false" x-data="{ tab: '{{ $mesAtivo }}' }">
            {{-- Abas por mês --}}
            <div class="flex flex-wrap gap-x-6 gap-y-2 border-b border-gray-100 px-5 py-3 dark:border-gray-700">
                @foreach ($meses as $m)
                    <button type="button" @click="tab = '{{ $m['cod'] }}'"
                            class="border-b-2 pb-1 text-sm font-medium transition"
                            :class="tab === '{{ $m['cod'] }}' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100'">
                        {{ $m['label'] }}
                    </button>
                @endforeach
            </div>

            {{-- Painel do mês selecionado --}}
            @foreach ($meses as $m)
                <div x-show="tab === '{{ $m['cod'] }}'" x-cloak
                     class="grid grid-cols-1 divide-y divide-gray-100 dark:divide-gray-700 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                    {{-- Contribuição 13º --}}
                    <div class="flex items-center justify-center gap-4 p-6">
                        @if ($m['decimo'])
                            {!! $icon !!}
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Contribuição 13° Salário Portaria</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $m['decimo']['qtd_pago'] }} <span class="font-normal text-gray-400">/</span> R$ {{ \App\Support\Valor::br($m['decimo']['valor_pago']) }}
                                </p>
                            </div>
                        @else
                            <span class="text-sm text-gray-300 dark:text-gray-600">Sem 13º neste mês</span>
                        @endif
                    </div>

                    {{-- Contribuição Portaria --}}
                    <div class="flex items-center justify-center gap-4 p-6">
                        @if ($m['portaria'])
                            {!! $icon !!}
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Contribuição Portaria</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $m['portaria']['qtd_pago'] }} <span class="font-normal text-gray-400">/{{ $m['portaria']['qtd_total'] }}</span> R$ {{ \App\Support\Valor::br($m['portaria']['valor_pago']) }}
                                </p>
                                <p class="text-xs text-gray-400">faltam {{ $m['portaria']['qtd_aberto'] }}</p>
                            </div>
                        @else
                            <span class="text-sm text-gray-300 dark:text-gray-600">Sem contribuição portaria neste mês</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </x-card>
    @endif
</div>

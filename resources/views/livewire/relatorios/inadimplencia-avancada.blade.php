<div>
    <x-page-header title="Inadimplência Avançada" subtitle="Ranking de devedores e meses em atraso">
        <x-slot:actions>
            <a href="{{ route('relatorios.index') }}" wire:navigate class="btn-secondary no-print">Relatórios</a>
            <a href="{{ route('relatorios.excel', ['relatorio' => 'inadimplencia-avancada', 'ano' => $ano, 'quadra' => $quadra]) }}" class="btn-success no-print">Excel</a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-6 no-print">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="form-label">Ano</label>
                <select wire:model.live="ano" class="form-select w-auto">
                    <option value="">Todos</option>
                    @foreach ($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Quadra</label>
                <select wire:model.live="quadra" class="form-select w-auto">
                    <option value="">Todas</option>
                    @foreach ($quadras as $q)<option value="{{ $q }}">{{ $q }}</option>@endforeach
                </select>
            </div>
        </div>
    </x-card>

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Total em aberto" :value="\App\Support\Valor::moeda($total)" color="red" />
        <x-stat label="Devedores" :value="$ranking->count()" color="amber" />
        <x-stat label="Maior dívida" :value="\App\Support\Valor::moeda($ranking->max('total_aberto') ?? 0)" color="gray" />
    </div>

    <x-card :padding="false">
        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr><th class="w-10">#</th><th>Morador</th><th>Quadra/Casa</th><th class="text-center">Meses em atraso</th><th class="text-right">Total devido</th><th class="text-center">Contato</th></tr>
                </thead>
                <tbody>
                    @forelse ($ranking as $d)
                        <tr wire:key="ia-{{ $d->id }}">
                            <td class="text-gray-400">{{ $loop->iteration }}</td>
                            <td class="font-medium text-gray-900 dark:text-white">{{ $d->nome }}</td>
                            <td class="text-gray-500">@if($d->quadra)Q{{ $d->quadra }} · {{ $d->numero }}@else—@endif</td>
                            <td class="text-center"><x-badge :color="$d->meses_atraso >= 6 ? 'red' : ($d->meses_atraso >= 3 ? 'amber' : 'gray')">{{ $d->meses_atraso }} mês(es)</x-badge></td>
                            <td class="text-right font-semibold text-red-600 dark:text-red-400">{{ \App\Support\Valor::br($d->total_aberto) }}</td>
                            <td class="text-center">
                                @if ($d->telefone)
                                    <a href="https://wa.me/55{{ preg_replace('/\D/', '', $d->telefone) }}?text={{ rawurlencode('Olá '.$d->nome.', consta(m) pendência(s) no valor de '.\App\Support\Valor::moeda($d->total_aberto).'. Podemos regularizar?') }}"
                                       target="_blank" class="inline-flex items-center gap-1 text-emerald-600 hover:underline">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.51 5.26l-.999 3.648 3.978-1.607z"/></svg>
                                        WhatsApp
                                    </a>
                                @else <span class="text-gray-400">—</span> @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-gray-400">Nenhuma inadimplência encontrada. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

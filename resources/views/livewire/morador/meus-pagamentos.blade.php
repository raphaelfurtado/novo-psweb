<div>
    <x-page-header title="Meus Pagamentos" subtitle="Acompanhe suas mensalidades e contribuições">
        <x-slot:actions>
            <select wire:model.live="situacao" class="form-select w-auto">
                <option value="">Toda situação</option>
                <option value="VENCIDO">⚠ Vencidos</option>
                @foreach (\App\Models\Pagamento::SITUACOES as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
            </select>
            <select wire:model.live="ano" class="form-select w-auto">
                @foreach ($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
            </select>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Total pago em {{ $ano }}" :value="\App\Support\Valor::moeda($totalPago)" color="green" />
        <x-stat label="Em aberto em {{ $ano }}" :value="\App\Support\Valor::moeda($totalAberto)" color="amber" />
        <x-stat label="Total geral" :value="\App\Support\Valor::moeda($totalPago + $totalAberto)" color="brand" />
    </div>

    <x-card :padding="false">
        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Referência</th>
                        <th>Tipo</th>
                        <th>Vencimento</th>
                        <th>Pagamento</th>
                        <th class="text-right">Valor</th>
                        <th>Situação</th>
                        <th class="w-20 text-right">Comprovante</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pagamentos as $p)
                        <tr wire:key="meu-{{ $p->id }}">
                            <td class="font-mono font-medium">{{ \App\Support\Referencia::abreviada($p->referencia) }}</td>
                            <td>{{ $p->tipoPagamento?->descricao ?? '—' }}</td>
                            <td class="text-gray-500">{{ optional($p->data_vencimento)->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-gray-500">{{ optional($p->data_pagamento)->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-right font-semibold">{{ \App\Support\Valor::br($p->valor) }}</td>
                            <td>
                                @if ($p->esta_vencido)
                                    <x-badge color="red">VENCIDO</x-badge>
                                @else
                                    <x-badge :situacao="$p->situacao" />
                                @endif
                            </td>
                            <td class="text-right">
                                @foreach ($p->comprovantes as $anexo)
                                    <a href="{{ route('anexos.download', $anexo) }}" target="_blank" class="inline-flex text-brand-500 hover:text-brand-700" title="Comprovante">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m4.5 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                    </a>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-gray-400">Nenhum pagamento encontrado em {{ $ano }}.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                    <tr>
                        <td colspan="4" class="px-4 py-3 font-semibold">Total · {{ $pagamentos->count() }} lançamento(s)</td>
                        <td class="px-4 py-3 text-right font-bold">{{ \App\Support\Valor::moeda($totalLista) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</div>

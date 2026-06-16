<div>
    <x-page-header title="Saídas / Despesas" subtitle="Despesas da associação (exceto folha de funcionários)">
        <x-slot:actions>
            <a href="{{ route('saidas.export', $this->paramsExport('pdf')) }}" target="_blank" class="btn-secondary">PDF</a>
            <a href="{{ route('saidas.export', $this->paramsExport('excel')) }}" class="btn-secondary">Excel</a>
            <a href="{{ route('saidas.create') }}" wire:navigate class="btn-primary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nova Despesa
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-stat label="Total filtrado" :value="\App\Support\Valor::moeda($total)" color="red" />
        <x-stat label="Lançamentos" :value="number_format($saidas->total(), 0, ',', '.')" color="gray" />
    </div>

    <x-card :padding="false">
        <div class="grid grid-cols-1 gap-3 border-b border-gray-100 p-4 dark:border-gray-700 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Buscar na observação…">
            </div>
            <select wire:model.live="ano" class="form-select">
                @foreach ($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
            </select>
            <select wire:model.live="mes" class="form-select">
                <option value="">Todos os meses</option>
                @foreach ($meses as $cod => $nome)<option value="{{ $cod }}">{{ ucfirst(mb_strtolower($nome)) }}</option>@endforeach
            </select>
            <select wire:model.live="tipo" class="form-select">
                <option value="">Todos os tipos</option>
                @foreach ($tipos as $t)<option value="{{ $t->codigo }}">{{ $t->descricao }}</option>@endforeach
            </select>
        </div>

        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Categoria</th>
                        <th>Recebedor</th>
                        <th>Observação</th>
                        <th>Ref.</th>
                        <th class="text-right">Valor</th>
                        <th class="w-28 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($saidas as $s)
                        <tr wire:key="sai-{{ $s->id }}">
                            <td>{{ optional($s->data_pagamento)->format('d/m/Y') }}</td>
                            <td class="font-medium text-gray-900 dark:text-white">{{ $s->tipoPagamento?->descricao ?? '—' }}</td>
                            <td class="text-gray-500">{{ $s->recebedor?->nome ?? '—' }}</td>
                            <td class="text-gray-500">{{ $s->observacao ?: '—' }}</td>
                            <td class="font-mono">{{ \App\Support\Referencia::abreviada($s->referencia) }}</td>
                            <td class="text-right font-semibold text-red-600 dark:text-red-400">{{ \App\Support\Valor::br($s->valor) }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    @foreach ($s->comprovantes as $anexo)
                                        <a href="{{ route('anexos.download', $anexo) }}" target="_blank" class="btn-ghost p-2" title="Comprovante">
                                            <svg class="h-4 w-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m4.5 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                        </a>
                                    @endforeach
                                    <a href="{{ route('saidas.edit', $s) }}" wire:navigate class="btn-ghost p-2" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </a>
                                    <button class="btn-ghost p-2 text-red-500" wire:click="excluir({{ $s->id }})"
                                            wire:confirm="Excluir esta despesa?" title="Excluir">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-gray-400">Nenhuma despesa encontrada.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                    <tr>
                        <td colspan="5" class="px-4 py-3 font-semibold">Total filtrado · {{ number_format($saidas->total(), 0, ',', '.') }} despesa(s)</td>
                        <td class="px-4 py-3 text-right font-bold text-red-600 dark:text-red-400">{{ \App\Support\Valor::moeda($total) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($saidas->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $saidas->links() }}</div>
        @endif
    </x-card>
</div>

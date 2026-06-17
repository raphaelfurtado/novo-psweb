<div>
    <x-page-header title="Pagamentos" subtitle="Mensalidades e demais receitas dos moradores">
        <x-slot:actions>
            <a href="{{ route('pagamentos.export', $this->paramsExport('pdf')) }}" target="_blank" class="btn-secondary">PDF</a>
            <a href="{{ route('pagamentos.export', $this->paramsExport('excel')) }}" class="btn-secondary">Excel</a>
            <a href="{{ route('pagamentos.reajuste') }}" wire:navigate class="btn-secondary">Reajustar valores</a>
            <a href="{{ route('pagamentos.gerar') }}" wire:navigate class="btn-secondary">Gerar do ano</a>
            <a href="{{ route('pagamentos.create') }}" wire:navigate class="btn-primary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo Pagamento
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-stat label="Total filtrado" :value="\App\Support\Valor::moeda($totalFiltro)" color="brand" />
        <x-stat label="Total pago" :value="\App\Support\Valor::moeda($totalPago)" color="green" />
        <x-stat label="Em aberto" :value="\App\Support\Valor::moeda($totalFiltro - $totalPago)" color="amber" />
        <x-stat label="Lançamentos" :value="number_format($pagamentos->total(), 0, ',', '.')" color="gray" />
    </div>

    <x-card :padding="false">
        <div class="grid grid-cols-1 gap-3 border-b border-gray-100 p-4 dark:border-gray-700 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Buscar morador…">
            </div>
            <select wire:model.live="ano" class="form-select">
                @foreach ($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
            </select>
            <select wire:model.live="mes" class="form-select">
                <option value="">Todos os meses</option>
                @foreach ($meses as $cod => $nome)<option value="{{ $cod }}">{{ ucfirst(mb_strtolower($nome)) }}</option>@endforeach
            </select>
            <select wire:model.live="situacao" class="form-select">
                <option value="">Toda situação</option>
                <option value="VENCIDO">⚠ Vencidos</option>
                @foreach (\App\Models\Pagamento::SITUACOES as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
            </select>
            <select wire:model.live="tipo" class="form-select">
                <option value="">Todos os tipos</option>
                @foreach ($tipos as $t)<option value="{{ $t->codigo }}">{{ $t->descricao }}</option>@endforeach
            </select>
            <select wire:model.live="quadra" class="form-select">
                <option value="">Toda quadra</option>
                @foreach ($quadras as $q)<option value="{{ $q }}">Quadra {{ $q }}</option>@endforeach
            </select>
            <input wire:model.live.debounce.300ms="numero" class="form-input" placeholder="Nº da casa" inputmode="numeric">
        </div>

        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Morador</th>
                        <th>Quadra</th>
                        <th>Casa</th>
                        <th>Referência</th>
                        <th>Tipo</th>
                        <th>Forma</th>
                        <th>Pagto</th>
                        <th class="text-right">Valor</th>
                        <th>Situação</th>
                        <th class="w-28 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pagamentos as $p)
                        <tr wire:key="pag-{{ $p->id }}">
                            <td class="font-medium text-gray-900 dark:text-white">{{ $p->usuario?->nome ?? '—' }}</td>
                            <td class="text-gray-500">{{ $p->usuario?->endereco?->quadra ?? '—' }}</td>
                            <td class="text-gray-500">{{ $p->usuario?->endereco?->numero ?? '—' }}</td>
                            <td class="font-mono">{{ \App\Support\Referencia::abreviada($p->referencia) }}</td>
                            <td>{{ $p->tipoPagamento?->descricao ?? '—' }}</td>
                            <td class="text-gray-500">{{ $p->formaPagamento?->descricao ?? '—' }}</td>
                            <td class="text-gray-500">{{ optional($p->data_pagamento)->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-right font-semibold">{{ \App\Support\Valor::br($p->valor) }}</td>
                            <td>
                                @if ($p->esta_vencido)
                                    <x-badge color="red">VENCIDO</x-badge>
                                @else
                                    <x-badge :situacao="$p->situacao" />
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    @foreach ($p->comprovantes as $anexo)
                                        <a href="{{ route('anexos.download', $anexo) }}" target="_blank" class="btn-ghost p-2" title="Comprovante">
                                            <svg class="h-4 w-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                        </a>
                                    @endforeach
                                    @if (in_array($p->situacao, ['ABERTO', 'PENDENTE'], true))
                                        <button class="btn-ghost p-2 text-emerald-600" wire:click="darBaixa({{ $p->id }})"
                                                wire:confirm="Dar baixa (marcar como PAGO hoje) neste pagamento de {{ \App\Support\Valor::moeda($p->valor) }}?" title="Dar baixa (PAGO)">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        </button>
                                    @endif
                                    <a href="{{ route('pagamentos.edit', array_merge(['pagamento' => $p->id], $this->filtrosAtuais())) }}" wire:navigate class="btn-ghost p-2" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </a>
                                    <button class="btn-ghost p-2 text-red-500" wire:click="excluir({{ $p->id }})"
                                            wire:confirm="Excluir este pagamento?" title="Excluir">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="py-10 text-center text-gray-400">Nenhum pagamento encontrado para os filtros.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                    <tr>
                        <td colspan="7" class="px-4 py-3 font-semibold">Total filtrado · {{ number_format($pagamentos->total(), 0, ',', '.') }} lançamento(s)</td>
                        <td class="px-4 py-3 text-right font-bold">{{ \App\Support\Valor::moeda($totalFiltro) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($pagamentos->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $pagamentos->links() }}</div>
        @endif
    </x-card>
</div>

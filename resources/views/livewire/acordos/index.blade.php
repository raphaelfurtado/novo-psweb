<div>
    <x-page-header title="Acordos" subtitle="Negociações de parcelas atrasadas (com desconto / parcelado)">
        <x-slot:actions>
            <a href="{{ route('conciliacao.index') }}" wire:navigate class="btn-secondary">Conciliação</a>
            <a href="{{ route('acordos.create') }}" wire:navigate class="btn-primary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo acordo
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-flash />

    <x-card :padding="false">
        <div class="grid grid-cols-1 gap-3 border-b border-gray-100 p-4 dark:border-gray-700 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Buscar morador…">
            </div>
            <select wire:model.live="situacao" class="form-select">
                <option value="">Toda situação</option>
                <option value="ABERTO">Aberto</option>
                <option value="QUITADO">Quitado</option>
                <option value="CANCELADO">Cancelado</option>
            </select>
        </div>

        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Morador</th>
                        <th>Data</th>
                        <th class="text-center">Parcelas</th>
                        <th class="text-right">Original</th>
                        <th class="text-right">Acordado</th>
                        <th class="text-right">Desconto</th>
                        <th class="text-right">Saldo</th>
                        <th>Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($acordos as $a)
                        <tr wire:key="acordo-{{ $a->id }}">
                            <td class="font-medium text-gray-900 dark:text-white">{{ $a->usuario?->nome ?? '—' }}</td>
                            <td class="text-gray-500">{{ optional($a->data_acordo)->format('d/m/Y') }}</td>
                            <td class="text-center">{{ $a->parcelas_count }}</td>
                            <td class="text-right text-gray-500">{{ \App\Support\Valor::br($a->valor_original) }}</td>
                            <td class="text-right font-semibold">{{ \App\Support\Valor::br($a->valor_acordado) }}</td>
                            <td class="text-right text-amber-600">{{ \App\Support\Valor::br($a->desconto) }}</td>
                            <td class="text-right font-semibold">{{ \App\Support\Valor::br($a->saldo) }}</td>
                            <td>
                                <x-badge :color="match($a->situacao){'QUITADO'=>'green','CANCELADO'=>'red',default=>'amber'}">{{ $a->situacao }}</x-badge>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    @if ($a->isAberto())
                                        <button class="btn-ghost p-2 text-emerald-600" title="Registrar pagamento" wire:click="abrirPagamento({{ $a->id }})">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        </button>
                                        <button class="btn-ghost p-2 text-red-500" title="Cancelar acordo"
                                                wire:click="cancelar({{ $a->id }})"
                                                wire:confirm="Cancelar este acordo? As parcelas voltam a ficar disponíveis para conciliação.">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-10 text-center text-gray-400">Nenhum acordo encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($acordos->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $acordos->links() }}</div>
        @endif
    </x-card>

    <x-modal model="mostrarPagamento" title="Registrar pagamento do acordo" maxWidth="sm">
        <div class="space-y-4">
            <x-field label="Valor recebido" hint="Para acordo parcelado, informe o valor desta parcela.">
                <x-input wire:model="pagamentoValor" inputmode="decimal" placeholder="0,00" />
            </x-field>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" wire:click="$set('mostrarPagamento', false)">Cancelar</button>
                <button type="button" class="btn-primary" wire:click="registrarPagamento">Registrar</button>
            </div>
        </div>
    </x-modal>
</div>

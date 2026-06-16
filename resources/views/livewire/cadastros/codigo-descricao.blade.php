<div>
    <x-page-header :title="$titulo">
        <x-slot:actions>
            <button type="button" class="btn-primary" wire:click="novo">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo
            </button>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <div class="border-b border-gray-100 p-4 dark:border-gray-700">
            <div class="relative max-w-xs">
                <svg class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input wire:model.live.debounce.300ms="search" class="form-input pl-10" placeholder="Buscar…">
            </div>
        </div>

        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-24">Código</th>
                        <th>Descrição</th>
                        <th class="w-28">Status</th>
                        <th class="w-32 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="row-{{ $row->id }}">
                            <td class="font-mono font-semibold">{{ $row->codigo }}</td>
                            <td class="font-medium text-gray-900 dark:text-white">{{ $row->descricao }}</td>
                            <td>
                                <x-badge :color="$row->registro_ativo ? 'green' : 'gray'">
                                    {{ $row->registro_ativo ? 'Ativo' : 'Inativo' }}
                                </x-badge>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button class="btn-ghost p-2" wire:click="editar({{ $row->id }})" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </button>
                                    <button class="btn-ghost p-2" wire:click="toggleAtivo({{ $row->id }})"
                                            wire:confirm="Confirma {{ $row->registro_ativo ? 'desativar' : 'ativar' }} este registro?"
                                            title="{{ $row->registro_ativo ? 'Desativar' : 'Ativar' }}">
                                        @if ($row->registro_ativo)
                                            <svg class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        @else
                                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        @endif
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-gray-400">Nenhum registro encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $rows->links() }}</div>
        @endif
    </x-card>

    <x-modal model="showModal" :title="($editingId ? 'Editar ' : 'Novo ').$singular">
        <form wire:submit="salvar" class="space-y-4">
            <x-field label="Código" name="codigo" required>
                <x-input wire:model="codigo" name="codigo" maxlength="2" placeholder="Ex.: 8" />
            </x-field>
            <x-field label="Descrição" name="descricao" required>
                <x-input wire:model="descricao" name="descricao" maxlength="50" placeholder="Descrição" class="uppercase" />
            </x-field>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" x-on:click="$wire.showModal = false">Cancelar</button>
                <button type="submit" class="btn-primary">
                    <span wire:loading.remove wire:target="salvar">Salvar</span>
                    <span wire:loading wire:target="salvar">Salvando…</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>

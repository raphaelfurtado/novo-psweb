<div>
    <x-page-header title="Recebedores" subtitle="Pessoas que recebem valores em nome do condomínio">
        <x-slot:actions>
            <button type="button" class="btn-primary" wire:click="novo">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo Recebedor
            </button>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <div class="border-b border-gray-100 p-4 dark:border-gray-700">
            <div class="relative max-w-xs">
                <svg class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input wire:model.live.debounce.300ms="search" class="form-input pl-10" placeholder="Buscar por nome ou telefone…">
            </div>
        </div>

        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Telefone 2</th>
                        <th>Nascimento</th>
                        <th class="w-20 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="rec-{{ $row->id }}">
                            <td class="font-medium text-gray-900 dark:text-white">{{ $row->nome }}</td>
                            <td>{{ $row->telefone }}</td>
                            <td class="text-gray-500">{{ $row->telefone_2 ?: '—' }}</td>
                            <td class="text-gray-500">{{ optional($row->data_nascimento)->format('d/m/Y') ?: '—' }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button class="btn-ghost p-2" wire:click="editar({{ $row->id }})" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </button>
                                    <button class="btn-ghost p-2 text-red-500" wire:click="excluir({{ $row->id }})"
                                            wire:confirm="Excluir o recebedor {{ $row->nome }}?" title="Excluir">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-gray-400">Nenhum recebedor encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $rows->links() }}</div>
        @endif
    </x-card>

    <x-modal model="showModal" :title="($editingId ? 'Editar' : 'Novo').' Recebedor'">
        <form wire:submit="salvar" class="space-y-4">
            <x-field label="Nome" name="nome" required>
                <x-input wire:model="nome" name="nome" class="uppercase" />
            </x-field>
            <div class="grid grid-cols-2 gap-4">
                <x-field label="Telefone" name="telefone" required>
                    <x-input wire:model="telefone" name="telefone" inputmode="numeric" />
                </x-field>
                <x-field label="Telefone 2" name="telefone_2">
                    <x-input wire:model="telefone_2" name="telefone_2" inputmode="numeric" />
                </x-field>
            </div>
            <x-field label="Data de nascimento" name="data_nascimento">
                <x-input type="date" wire:model="data_nascimento" name="data_nascimento" />
            </x-field>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" x-on:click="$wire.showModal = false">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar</button>
            </div>
        </form>
    </x-modal>
</div>

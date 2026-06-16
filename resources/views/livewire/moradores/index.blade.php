<div>
    <x-page-header title="Moradores" subtitle="Cadastro de moradores e endereços">
        <x-slot:actions>
            <a href="{{ route('pagamentos.gerar') }}" wire:navigate class="btn-secondary">Gerar pagamentos do ano</a>
            <a href="{{ route('moradores.create') }}" wire:navigate class="btn-primary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo Morador
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative max-w-xs flex-1">
                <svg class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input wire:model.live.debounce.300ms="search" class="form-input pl-10" placeholder="Buscar por nome ou telefone…">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" wire:model.live="semPagamento" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                Somente sem pagamento em {{ $ano }}
            </label>
        </div>

        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Quadra / Casa</th>
                        <th>Perfil</th>
                        <th class="w-20 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $u)
                        <tr wire:key="mor-{{ $u->id }}">
                            <td>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $u->nome }}</div>
                                @if ($u->pagamentos_ano === 0)
                                    <x-badge color="amber" class="mt-1">Sem pagamento em {{ $ano }}</x-badge>
                                @endif
                            </td>
                            <td>{{ $u->telefone }}</td>
                            <td class="text-gray-500">
                                @if ($u->endereco)
                                    Q{{ $u->endereco->quadra }} · Nº {{ $u->endereco->numero }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td>
                                <x-badge :color="$u->role === 'admin' ? 'blue' : 'gray'">
                                    {{ $u->role === 'admin' ? 'Administrador' : 'Morador' }}
                                </x-badge>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('moradores.edit', $u) }}" wire:navigate class="btn-ghost p-2" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </a>
                                    <button class="btn-ghost p-2 text-red-500" wire:click="confirmar({{ $u->id }})" title="Excluir">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-gray-400">Nenhum morador encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $rows->links() }}</div>
        @endif
    </x-card>

    <x-modal model="showConfirm" title="Excluir morador">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Tem certeza que deseja excluir <strong>{{ $confirmandoNome }}</strong>?
            O endereço também será removido.
        </p>

        @if ($confirmandoPagamentos > 0)
            <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-200 dark:ring-amber-800">
                Este morador possui <strong>{{ $confirmandoPagamentos }} pagamento(s)</strong> registrado(s).
            </div>
            <label class="mt-3 flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="checkbox" wire:model="excluirPagamentos" class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span>Excluir também os <strong>{{ $confirmandoPagamentos }} pagamento(s)</strong> deste morador (e seus comprovantes). Esta ação é irreversível.</span>
            </label>
            @error('excluirPagamentos')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
        @endif

        <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="btn-secondary" x-on:click="$wire.showConfirm = false">Cancelar</button>
            <button type="button" class="btn-danger" wire:click="excluir">
                <span wire:loading.remove wire:target="excluir">Excluir</span>
                <span wire:loading wire:target="excluir">Excluindo…</span>
            </button>
        </div>
    </x-modal>
</div>

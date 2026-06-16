<div>
    <x-page-header title="Novo Acordo" subtitle="Negocie um bloco de parcelas atrasadas">
        <x-slot:actions>
            <a href="{{ route('acordos.index') }}" wire:navigate class="btn-secondary">Voltar</a>
        </x-slot:actions>
    </x-page-header>

    <x-flash />

    <form wire:submit="salvar" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Morador e parcelas">
                <x-field label="Morador" name="moradorId" required>
                    <x-select wire:model.live="moradorId" name="moradorId">
                        <option value="">— selecione —</option>
                        @foreach ($moradores as $m)
                            <option value="{{ $m->id }}">{{ $m->nome }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                @if ($moradorId)
                    <div class="mt-4">
                        <div class="mb-1 flex items-center justify-between">
                            <p class="form-label mb-0">Parcelas em aberto (mais antiga primeiro)</p>
                            <button type="button" class="text-xs font-semibold text-brand-600 hover:underline" wire:click="selecionarTodas">
                                Selecionar todas
                            </button>
                        </div>
                        <div class="max-h-72 space-y-1 overflow-y-auto rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                            @forelse ($parcelas as $p)
                                <label wire:key="acp-{{ $p->id }}"
                                       class="flex cursor-pointer items-center justify-between rounded-md px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <span class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" wire:model.live="parcelasSel.{{ $p->id }}"
                                               class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                        <span class="font-mono">{{ \App\Support\Referencia::abreviada($p->referencia) }}</span>
                                        @if ($p->esta_vencido)<x-badge color="red">vencida</x-badge>@endif
                                    </span>
                                    <span class="font-semibold">{{ \App\Support\Valor::moeda($p->saldo_aberto) }}</span>
                                </label>
                            @empty
                                <p class="px-2 py-4 text-center text-sm text-gray-400">Este morador não tem parcelas em aberto.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-4">
            <x-card title="Condições do acordo">
                <div class="space-y-4">
                    <div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-900/40">
                        <div class="flex justify-between"><span>Dívida selecionada</span><span class="font-semibold">{{ \App\Support\Valor::moeda($original) }}</span></div>
                        <div class="mt-1 flex justify-between border-t border-gray-200 pt-1 dark:border-gray-700">
                            <span class="text-amber-600">Desconto</span>
                            <span class="font-bold text-amber-600">{{ \App\Support\Valor::moeda($desconto) }}</span>
                        </div>
                    </div>

                    <x-field label="Valor acordado" name="valorAcordado" required hint="O que o morador vai pagar no total.">
                        <x-input wire:model.live.debounce.400ms="valorAcordado" name="valorAcordado" inputmode="decimal" placeholder="0,00" />
                    </x-field>

                    <x-field label="Em quantas vezes" name="numParcelas" required hint="1 = à vista.">
                        <x-input type="number" wire:model="numParcelas" name="numParcelas" min="1" max="60" />
                    </x-field>

                    <x-field label="Observação" name="observacao">
                        <textarea wire:model="observacao" class="form-textarea" rows="2" placeholder="Opcional"></textarea>
                    </x-field>

                    <button type="submit" class="btn-primary w-full">
                        <span wire:loading.remove wire:target="salvar">Criar acordo</span>
                        <span wire:loading wire:target="salvar">Salvando…</span>
                    </button>
                </div>
            </x-card>
        </div>
    </form>
</div>

<div>
    <x-page-header title="Gerar Pagamentos do Ano" subtitle="Cria as mensalidades (tipo MENSALIDADE) em aberto para todos os moradores">
        <x-slot:actions>
            <a href="{{ route('pagamentos.index') }}" wire:navigate class="btn-secondary">Voltar</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card title="Parâmetros da geração">
                <form wire:submit="gerar" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-field label="Ano" name="ano" required>
                            <x-input wire:model.live="ano" name="ano" inputmode="numeric" maxlength="4" />
                        </x-field>
                        <x-field label="Valor da mensalidade" name="valor" required hint="Ex.: 80,00">
                            <x-input wire:model="valor" name="valor" inputmode="decimal" placeholder="0,00" />
                        </x-field>
                        <x-field label="Dia do vencimento" name="dia_vencimento" required>
                            <x-input type="number" wire:model="dia_vencimento" name="dia_vencimento" min="1" max="28" />
                        </x-field>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="form-label mb-0">Competências a gerar</label>
                            <div class="flex gap-2 text-xs">
                                <button type="button" class="text-brand-600 hover:underline" wire:click="marcarTodos">Marcar todos</button>
                                <span class="text-gray-300">·</span>
                                <button type="button" class="text-brand-600 hover:underline" wire:click="limparMeses">Limpar</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                            @foreach ($meses as $cod => $nome)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:border-gray-700 dark:has-[:checked]:bg-brand-900/20">
                                    <input type="checkbox" wire:model.live="mesesSelecionados" value="{{ $cod }}"
                                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    <span>{{ ucfirst(mb_strtolower(\App\Support\Referencia::MESES_ABREV[$cod] ?? $nome)) }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('mesesSelecionados')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-200 dark:ring-amber-800">
                        Serão criadas <strong>{{ count($mesesSelecionados) }}</strong> competência(s) de {{ $ano }} para cada morador.
                        Meses que já possuem mensalidade são <strong>ignorados</strong> (não há duplicação) — ideal para morador
                        que entrou no meio do ano.
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary"
                                wire:confirm="Gerar {{ count($mesesSelecionados) }} competência(s) de {{ $ano }} para os moradores?">
                            <span wire:loading.remove wire:target="gerar">Gerar mensalidades</span>
                            <span wire:loading wire:target="gerar">Processando…</span>
                        </button>
                    </div>
                </form>

                @if ($resultado)
                    <div class="mt-6 grid grid-cols-3 gap-4 border-t border-gray-100 pt-6 dark:border-gray-700">
                        <x-stat label="Criadas" :value="$resultado['criados']" color="green" />
                        <x-stat label="Já existentes" :value="$resultado['pulados']" color="amber" />
                        <x-stat label="Moradores" :value="$resultado['moradores']" color="gray" />
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-4">
            <x-stat label="Moradores cadastrados" :value="$totalMoradores" color="brand" />
            <x-stat label="Sem mensalidade em {{ $ano }}" :value="$semPagamento" color="amber" />
        </div>
    </div>
</div>

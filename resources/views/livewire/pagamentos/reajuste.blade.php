<div>
    <x-page-header title="Reajustar Valores (em aberto)" subtitle="Atualiza o valor de vários lançamentos de uma vez — só os que estão em aberto">
        <x-slot:actions>
            <a href="{{ route('pagamentos.index') }}" wire:navigate class="btn-secondary">Voltar</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card title="Filtros e novo valor">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <x-field label="Ano" name="ano" required>
                        <x-input wire:model.live="ano" name="ano" inputmode="numeric" maxlength="4" />
                    </x-field>
                    <x-field label="Mês (opcional)">
                        <x-select wire:model.live="mes">
                            <option value="">Todos os meses</option>
                            @foreach ($meses as $cod => $nome)<option value="{{ $cod }}">{{ ucfirst(mb_strtolower($nome)) }}</option>@endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Tipo">
                        <x-select wire:model.live="tipo">
                            <option value="">Todos os tipos</option>
                            @foreach ($tipos as $t)<option value="{{ $t->codigo }}">{{ $t->descricao }}</option>@endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Morador (opcional)" class="sm:col-span-2">
                        <x-select wire:model.live="usuario">
                            <option value="">Todos os moradores</option>
                            @foreach ($moradores as $m)<option value="{{ $m->id }}">{{ $m->nome }}</option>@endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Somente os que estão com o valor (opcional)" hint="Ex.: 80,00 — deixa em branco para qualquer valor.">
                        <x-input wire:model.live.debounce.400ms="valorAtual" inputmode="decimal" placeholder="qualquer" />
                    </x-field>
                </div>

                <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-700">
                    <x-field label="Novo valor" name="novoValor" required hint="Ex.: 90,00">
                        <x-input wire:model.live.debounce.400ms="novoValor" name="novoValor" inputmode="decimal" placeholder="0,00" class="max-w-xs" />
                    </x-field>
                </div>

                <div class="mt-5 flex items-center gap-3">
                    <button type="button" class="btn-primary"
                            wire:click="aplicar"
                            wire:confirm="Atualizar {{ $qtd }} lançamento(s) em aberto para o novo valor? Esta ação altera vários registros."
                            @disabled($qtd === 0)>
                        <span wire:loading.remove wire:target="aplicar">Aplicar reajuste</span>
                        <span wire:loading wire:target="aplicar">Aplicando…</span>
                    </button>
                    <span class="text-sm text-gray-500">Pagamentos <strong>PAGOS não são alterados</strong>.</span>
                </div>

                @if ($resultado)
                    <div class="mt-5 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-200 dark:ring-emerald-800">
                        {{ $resultado['afetados'] }} lançamento(s) atualizado(s) para {{ \App\Support\Valor::moeda($resultado['novoValor']) }}.
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-4">
            <x-stat label="Lançamentos a alterar" :value="number_format($qtd, 0, ',', '.')" color="amber" />
            <x-stat label="Total atual (em aberto)" :value="\App\Support\Valor::moeda($totalAtual)" color="gray" />
            @if ($totalNovo !== null)
                <x-stat label="Total após reajuste" :value="\App\Support\Valor::moeda($totalNovo)" color="green"
                        :sub="($totalNovo - $totalAtual >= 0 ? '+' : '').\App\Support\Valor::moeda($totalNovo - $totalAtual).' de diferença'" />
            @endif
        </div>
    </div>
</div>

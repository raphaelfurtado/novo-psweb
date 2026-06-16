<div>
    <x-page-header :title="$editando ? 'Editar Pagamento' : 'Novo Pagamento'">
        <x-slot:actions>
            <a href="{{ route('pagamentos.index') }}" wire:navigate class="btn-secondary">Voltar</a>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="salvar" class="space-y-6">
        <x-card title="Dados do pagamento">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field class="lg:col-span-2" label="Morador" name="id_usuario" required>
                    <x-select wire:model="id_usuario" name="id_usuario">
                        <option value="">Selecione…</option>
                        @foreach ($moradores as $m)<option value="{{ $m->id }}">{{ $m->nome }}</option>@endforeach
                    </x-select>
                </x-field>
                <x-field label="Recebedor" name="id_recebedor">
                    <x-select wire:model="id_recebedor" name="id_recebedor">
                        <option value="">—</option>
                        @foreach ($recebedores as $r)<option value="{{ $r->id }}">{{ $r->nome }}</option>@endforeach
                    </x-select>
                </x-field>
                <x-field label="Tipo" name="id_tipo_pagamento" required>
                    <x-select wire:model="id_tipo_pagamento" name="id_tipo_pagamento">
                        <option value="">Selecione…</option>
                        @foreach ($tipos as $t)<option value="{{ $t->codigo }}">{{ $t->descricao }}</option>@endforeach
                    </x-select>
                </x-field>
                <x-field label="Forma" name="id_forma_pagamento">
                    <x-select wire:model="id_forma_pagamento" name="id_forma_pagamento">
                        <option value="">—</option>
                        @foreach ($formas as $f)<option value="{{ $f->codigo }}">{{ $f->descricao }}</option>@endforeach
                    </x-select>
                </x-field>
                <x-field label="Situação" name="situacao" required>
                    <x-select wire:model="situacao" name="situacao">
                        @foreach (\App\Models\Pagamento::SITUACOES as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                    </x-select>
                </x-field>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-field label="Mês de referência" name="mes" required>
                    <x-select wire:model="mes" name="mes">
                        @foreach ($meses as $cod => $nome)<option value="{{ $cod }}">{{ ucfirst(mb_strtolower($nome)) }}</option>@endforeach
                    </x-select>
                </x-field>
                <x-field label="Ano de referência" name="ano" required>
                    <x-input wire:model="ano" name="ano" inputmode="numeric" maxlength="4" />
                </x-field>
                <x-field label="Valor" name="valor" required hint="Ex.: 80,00">
                    <x-input wire:model="valor" name="valor" inputmode="decimal" placeholder="0,00" />
                </x-field>
                <x-field label="Data do pagamento" name="data_pagamento">
                    <x-input type="date" wire:model="data_pagamento" name="data_pagamento" />
                </x-field>
                <x-field label="Vencimento" name="data_vencimento">
                    <x-input type="date" wire:model="data_vencimento" name="data_vencimento" />
                </x-field>
                <x-field class="sm:col-span-2 lg:col-span-3" label="Observação" name="observacao">
                    <x-input wire:model="observacao" name="observacao" maxlength="100" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Comprovante (opcional)">
            @if ($comprovantes->isNotEmpty())
                <ul class="mb-4 space-y-2">
                    @foreach ($comprovantes as $c)
                        <li class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-gray-900">
                            <a href="{{ route('anexos.download', $c) }}" target="_blank" class="text-brand-600 hover:underline">{{ $c->original_name }}</a>
                            <button type="button" class="text-red-500 hover:text-red-700" wire:click="removerComprovante({{ $c->id }})"
                                    wire:confirm="Remover este comprovante?">Remover</button>
                        </li>
                    @endforeach
                </ul>
            @endif

            <x-field name="anexo" hint="PDF ou imagem, até 10 MB.">
                <input type="file" wire:model="anexo" class="form-input">
            </x-field>
            <div wire:loading wire:target="anexo" class="mt-1 text-xs text-gray-400">Enviando arquivo…</div>
        </x-card>

        <div class="flex justify-end gap-2">
            <a href="{{ route('pagamentos.index') }}" wire:navigate class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                <span wire:loading.remove wire:target="salvar">Salvar</span>
                <span wire:loading wire:target="salvar">Salvando…</span>
            </button>
        </div>
    </form>
</div>

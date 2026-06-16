<div>
    <x-page-header :title="$editando ? 'Editar Morador' : 'Novo Morador'"
                   :subtitle="$editando ? $user->nome : 'Cadastro de morador e endereço'">
        <x-slot:actions>
            <a href="{{ route('moradores.index') }}" wire:navigate class="btn-secondary">Voltar</a>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="salvar" class="space-y-6">
        <x-card title="Dados do morador">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field class="lg:col-span-2" label="Nome" name="nome" required>
                    <x-input wire:model="nome" name="nome" />
                </x-field>
                <x-field label="Aniversário" name="aniversario">
                    <x-input type="date" wire:model="aniversario" name="aniversario" />
                </x-field>
                <x-field label="Telefone" name="telefone" required hint="Usado para login.">
                    <x-input wire:model="telefone" name="telefone" inputmode="numeric" />
                </x-field>
                <x-field label="Telefone 2" name="telefone_2">
                    <x-input wire:model="telefone_2" name="telefone_2" inputmode="numeric" />
                </x-field>
                <x-field label="CPF" name="cpf" hint="Usado para casar o PIX recebido (conciliação).">
                    <x-input wire:model="cpf" name="cpf" inputmode="numeric" placeholder="000.000.000-00" />
                </x-field>
                <x-field label="Perfil" name="role" required>
                    <x-select wire:model="role" name="role">
                        <option value="user">Morador</option>
                        <option value="admin">Administrador</option>
                    </x-select>
                </x-field>
                <x-field label="{{ $editando ? 'Nova senha (opcional)' : 'Senha' }}" name="senha" :required="! $editando"
                         hint="{{ $editando ? 'Deixe em branco para manter a atual.' : 'Mínimo de 6 caracteres.' }}">
                    <x-input type="password" wire:model="senha" name="senha" autocomplete="new-password" />
                </x-field>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-field label="Possui acordo?" name="possui_acordo">
                    <x-select wire:model="possui_acordo" name="possui_acordo">
                        <option value="N">Não</option>
                        <option value="S">Sim</option>
                    </x-select>
                </x-field>
                <x-field class="sm:col-span-2" label="Detalhes do acordo" name="acordo">
                    <x-input wire:model="acordo" name="acordo" placeholder="Opcional" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Endereço">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-field class="sm:col-span-2" label="Rua" name="rua">
                    <x-input wire:model="rua" name="rua" />
                </x-field>
                <x-field label="Número" name="numero" required>
                    <x-input wire:model="numero" name="numero" maxlength="5" />
                </x-field>
                <x-field label="Quadra" name="quadra" required>
                    <x-input type="number" wire:model="quadra" name="quadra" />
                </x-field>
                <x-field label="Qtd. de lotes" name="qtd_lote">
                    <x-input type="number" wire:model="qtd_lote" name="qtd_lote" min="1" />
                </x-field>
            </div>
        </x-card>

        <div class="flex justify-end gap-2">
            <a href="{{ route('moradores.index') }}" wire:navigate class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                <span wire:loading.remove wire:target="salvar">Salvar</span>
                <span wire:loading wire:target="salvar">Salvando…</span>
            </button>
        </div>
    </form>
</div>

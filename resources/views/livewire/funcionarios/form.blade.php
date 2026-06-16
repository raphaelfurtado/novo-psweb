<div>
    <x-page-header :title="$editando ? 'Editar Funcionário' : 'Novo Funcionário'"
                   :subtitle="$editando ? $funcionario->nome_completo : 'Preencha os dados do funcionário'">
        <x-slot:actions>
            <a href="{{ route('funcionarios.index') }}" wire:navigate class="btn-secondary">Voltar</a>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="salvar" class="space-y-6">
        <x-card title="Dados pessoais">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field class="sm:col-span-2 lg:col-span-2" label="Nome completo" name="nome_completo" required>
                    <x-input wire:model="nome_completo" name="nome_completo" />
                </x-field>
                <x-field label="Data de nascimento" name="data_nascimento">
                    <x-input type="date" wire:model="data_nascimento" name="data_nascimento" />
                </x-field>
                <x-field label="CPF" name="cpf">
                    <x-input wire:model.blur="cpf" name="cpf" inputmode="numeric" maxlength="11" placeholder="Somente números" />
                </x-field>
                <x-field label="RG" name="rg">
                    <x-input wire:model="rg" name="rg" />
                </x-field>
                <x-field label="WhatsApp" name="telefone_whatsapp">
                    <x-input wire:model="telefone_whatsapp" name="telefone_whatsapp" inputmode="numeric" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Endereço">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-field class="sm:col-span-2" label="Endereço" name="endereco_completo">
                    <x-input wire:model="endereco_completo" name="endereco_completo" />
                </x-field>
                <x-field label="Complemento" name="complemento">
                    <x-input wire:model="complemento" name="complemento" />
                </x-field>
                <x-field label="CEP" name="cep">
                    <x-input wire:model.blur="cep" name="cep" inputmode="numeric" maxlength="8" />
                </x-field>
                <x-field class="sm:col-span-2" label="Cidade" name="cidade">
                    <x-input wire:model="cidade" name="cidade" />
                </x-field>
                <x-field label="Estado (UF)" name="estado">
                    <x-input wire:model="estado" name="estado" maxlength="2" class="uppercase" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Dados financeiros e bancários">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field label="Salário" name="salario" hint="Ex.: 1.500,00">
                    <x-input wire:model="salario" name="salario" inputmode="decimal" placeholder="0,00" />
                </x-field>
                <x-field label="Titular da conta" name="nome_titular_conta">
                    <x-input wire:model="nome_titular_conta" name="nome_titular_conta" />
                </x-field>
                <x-field label="Banco" name="banco">
                    <x-input wire:model="banco" name="banco" />
                </x-field>
                <x-field label="Agência" name="agencia">
                    <x-input wire:model="agencia" name="agencia" />
                </x-field>
                <x-field label="Conta" name="numero_conta">
                    <x-input wire:model="numero_conta" name="numero_conta" />
                </x-field>
                <x-field label="Tipo de conta" name="tipo_conta">
                    <x-select wire:model="tipo_conta" name="tipo_conta">
                        <option value="">—</option>
                        @foreach (\App\Models\Funcionario::TIPOS_CONTA as $cod => $desc)
                            <option value="{{ $cod }}">{{ $desc }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field class="sm:col-span-2 lg:col-span-3" label="Chave PIX" name="chave_pix">
                    <x-input wire:model="chave_pix" name="chave_pix" />
                </x-field>
                <x-field class="sm:col-span-2 lg:col-span-3" label="Observação" name="observacao">
                    <x-textarea wire:model="observacao" name="observacao" />
                </x-field>
            </div>
        </x-card>

        <div class="flex justify-end gap-2">
            <a href="{{ route('funcionarios.index') }}" wire:navigate class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                <span wire:loading.remove wire:target="salvar">Salvar</span>
                <span wire:loading wire:target="salvar">Salvando…</span>
            </button>
        </div>
    </form>
</div>

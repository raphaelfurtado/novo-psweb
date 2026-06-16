<div>
    <x-page-header title="Trocar Senha" subtitle="Atualize a senha de acesso ao sistema" />

    <div class="max-w-md">
        <x-card>
            <form wire:submit="salvar" class="space-y-4">
                <x-field label="Senha atual" name="atual" required>
                    <x-input type="password" wire:model="atual" name="atual" autocomplete="current-password" />
                </x-field>
                <x-field label="Nova senha" name="nova" required hint="Mínimo de 6 caracteres.">
                    <x-input type="password" wire:model="nova" name="nova" autocomplete="new-password" />
                </x-field>
                <x-field label="Confirmar nova senha" name="nova_confirmation" required>
                    <x-input type="password" wire:model="nova_confirmation" name="nova_confirmation" autocomplete="new-password" />
                </x-field>
                <div class="pt-2">
                    <button type="submit" class="btn-primary">Salvar nova senha</button>
                </div>
            </form>
        </x-card>
    </div>
</div>

<div class="card p-6 sm:p-8">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Política de Privacidade</h2>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Para usar o sistema, leia e aceite o tratamento dos seus dados.
    </p>

    <div class="mt-4 max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
        <p class="mb-2">
            Este sistema trata dados pessoais (nome, telefone, endereço e informações financeiras) com a
            finalidade exclusiva de gerir as contribuições e a prestação de contas do condomínio, conforme a
            Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018).
        </p>
        <p class="mb-2">
            Os dados não são compartilhados com terceiros para fins comerciais e são mantidos enquanto durar a
            relação com o condomínio. Você pode solicitar a atualização ou exclusão dos seus dados à administração.
        </p>
        <p>Ao aceitar, você concorda com o tratamento dos dados nos termos acima.</p>
    </div>

    <form wire:submit="aceitar" class="mt-5 space-y-4">
        <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" wire:model="aceito" class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            <span>Li e aceito a Política de Privacidade.</span>
        </label>
        @error('aceito')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary flex-1">Aceitar e continuar</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-secondary">Sair</button>
            </form>
        </div>
    </form>
</div>

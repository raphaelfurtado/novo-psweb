<div>
    <x-page-header :title="'Olá, '.auth()->user()->primeiro_nome" subtitle="Resumo da sua situação no condomínio">
        <x-slot:actions>
            <select wire:model.live="ano" class="form-select w-auto">
                @foreach ($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
            </select>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Total pago em {{ $ano }}" :value="\App\Support\Valor::moeda($totalPago)" color="green" />
        <x-stat label="Em aberto em {{ $ano }}" :value="\App\Support\Valor::moeda($totalAberto)" color="amber" />
        <x-stat label="Total geral" :value="\App\Support\Valor::moeda($totalPago + $totalAberto)" color="brand" />
    </div>

    <x-card title="Próximos vencimentos em aberto">
        @if ($proximos->isEmpty())
            <p class="py-6 text-center text-sm text-gray-400">Você não possui pagamentos em aberto. 🎉</p>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($proximos as $p)
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $p->tipoPagamento?->descricao ?? 'Pagamento' }}</p>
                            <p class="text-xs text-gray-500">
                                Ref. {{ \App\Support\Referencia::abreviada($p->referencia) }}
                                @if ($p->data_vencimento) · vence {{ $p->data_vencimento->format('d/m/Y') }}@endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-semibold">{{ \App\Support\Valor::moeda($p->valor) }}</span>
                            <x-badge :situacao="$p->situacao" />
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">
                <a href="{{ route('meus-pagamentos') }}" wire:navigate class="btn-secondary">Ver todos os pagamentos</a>
            </div>
        @endif
    </x-card>
</div>

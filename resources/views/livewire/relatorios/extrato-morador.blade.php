<div>
    <x-page-header title="Extrato do Morador" subtitle="Todos os pagamentos de um morador">
        <x-slot:actions>
            <a href="{{ route('relatorios.index') }}" wire:navigate class="btn-secondary no-print">Relatórios</a>
            @if ($morador)
                <a href="{{ route('relatorios.pdf', ['relatorio' => 'extrato-morador', 'usuario' => $morador->id, 'ano' => $ano]) }}" target="_blank" class="btn-primary no-print">Exportar PDF</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-6 no-print">
        <div class="flex flex-wrap items-end gap-4">
            <div class="min-w-64 flex-1">
                <label class="form-label">Morador</label>
                <select wire:model.live="usuario" class="form-select">
                    <option value="">Selecione um morador…</option>
                    @foreach ($moradores as $m)<option value="{{ $m->id }}">{{ $m->nome }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Ano</label>
                <select wire:model.live="ano" class="form-select w-auto">
                    @foreach ($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                </select>
            </div>
        </div>
    </x-card>

    @if (! $morador)
        <x-card><p class="py-8 text-center text-gray-400">Selecione um morador para ver o extrato.</p></x-card>
    @else
        <div class="mb-4 rounded-xl bg-gray-50 p-4 dark:bg-gray-900">
            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $morador->nome }}</p>
            <p class="text-sm text-gray-500">
                {{ $morador->telefone }}
                @if ($morador->endereco) · Quadra {{ $morador->endereco->quadra }}, nº {{ $morador->endereco->numero }}@endif
            </p>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat label="Total pago em {{ $ano }}" :value="\App\Support\Valor::moeda($totalPago)" color="green" />
            <x-stat label="Em aberto em {{ $ano }}" :value="\App\Support\Valor::moeda($totalAberto)" color="amber" />
            <x-stat label="Total geral" :value="\App\Support\Valor::moeda($totalPago + $totalAberto)" color="brand" />
        </div>

        <x-card :padding="false">
            <div class="table-wrap border-0">
                <table class="table-base">
                    <thead><tr><th>Referência</th><th>Tipo</th><th>Forma</th><th>Vencimento</th><th>Pagamento</th><th class="text-right">Valor</th><th>Situação</th></tr></thead>
                    <tbody>
                        @forelse ($pagamentos as $p)
                            <tr wire:key="ext-{{ $p->id }}">
                                <td class="font-mono">{{ \App\Support\Referencia::abreviada($p->referencia) }}</td>
                                <td>{{ $p->tipoPagamento?->descricao ?? '—' }}</td>
                                <td class="text-gray-500">{{ $p->formaPagamento?->descricao ?? '—' }}</td>
                                <td class="text-gray-500">{{ optional($p->data_vencimento)->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-gray-500">{{ optional($p->data_pagamento)->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-right font-semibold">{{ \App\Support\Valor::br($p->valor) }}</td>
                                <td><x-badge :situacao="$p->situacao" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-10 text-center text-gray-400">Sem pagamentos em {{ $ano }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</div>

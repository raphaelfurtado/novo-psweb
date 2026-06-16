<div>
    <x-page-header title="Conciliação por Extrato (OFX)"
                   subtitle="Importe o extrato do banco e dê baixa nos pagamentos automaticamente">
        <x-slot:actions>
            <a href="{{ route('acordos.index') }}" wire:navigate class="btn-secondary">Acordos</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <x-card title="Importar extrato">
                <form wire:submit="importar" class="space-y-4">
                    <x-field label="Arquivo .ofx" name="arquivo"
                             hint="Exporte o extrato do Banco do Brasil no formato OFX (Money 2000).">
                        <input type="file" wire:model="arquivo" accept=".ofx,.txt"
                               class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0
                                      file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold
                                      file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-300">
                    </x-field>

                    <div wire:loading wire:target="arquivo" class="text-sm text-gray-500">Carregando arquivo…</div>

                    <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="importar,arquivo">
                        <span wire:loading.remove wire:target="importar">Importar e conciliar</span>
                        <span wire:loading wire:target="importar">Processando…</span>
                    </button>
                </form>

                <div class="mt-4 rounded-lg bg-brand-50 p-3 text-xs text-brand-800 ring-1 ring-brand-100 dark:bg-brand-900/20 dark:text-brand-200 dark:ring-brand-800">
                    Cada crédito é casado ao morador por <strong>CPF</strong> (ou nome) e a baixa segue a regra
                    <strong>FIFO</strong> (parcela mais antiga primeiro). Transações repetidas são ignoradas automaticamente.
                </div>
            </x-card>
        </div>

        <div class="lg:col-span-2">
            <x-card :padding="false" title="Extratos importados">
                <div class="table-wrap border-0">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Importado em</th>
                                <th>Arquivo</th>
                                <th>Período</th>
                                <th class="text-right">Créditos</th>
                                <th class="text-center">Situação</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lotes as $lote)
                                <tr wire:key="lote-{{ $lote->id }}">
                                    <td class="text-gray-500">{{ optional($lote->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="font-medium text-gray-900 dark:text-white">{{ $lote->arquivo ?? '—' }}</td>
                                    <td class="text-gray-500">
                                        {{ optional($lote->periodo_inicio)->format('d/m') }} –
                                        {{ optional($lote->periodo_fim)->format('d/m/Y') }}
                                    </td>
                                    <td class="text-right font-semibold">
                                        {{ \App\Support\Valor::moeda($lote->total_creditos) }}
                                        <span class="text-xs text-gray-400">({{ $lote->recebimentos_count }})</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($lote->pendentes_count > 0)
                                            <x-badge color="amber">{{ $lote->pendentes_count }} pendente(s)</x-badge>
                                        @else
                                            <x-badge color="green">Concluído</x-badge>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('conciliacao.revisar', $lote) }}" wire:navigate class="btn-secondary px-3 py-1.5 text-xs">
                                            Revisar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-gray-400">Nenhum extrato importado ainda.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($lotes->hasPages())
                    <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $lotes->links() }}</div>
                @endif
            </x-card>
        </div>
    </div>
</div>

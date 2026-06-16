<div>
    <x-page-header title="Revisar Conciliação"
                   subtitle="{{ $lote->arquivo }} · {{ optional($lote->periodo_inicio)->format('d/m/Y') }} a {{ optional($lote->periodo_fim)->format('d/m/Y') }}">
        <x-slot:actions>
            <a href="{{ route('conciliacao.index') }}" wire:navigate class="btn-secondary">Voltar</a>
            @if ($resumo['verdes'] > 0)
                <button class="btn-success" wire:click="confirmarVerdes"
                        wire:confirm="Dar baixa automática em {{ $resumo['verdes'] }} recebimento(s) que casaram exatamente?">
                    Confirmar {{ $resumo['verdes'] }} verde(s)
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-flash />

    <div class="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-stat label="Recebimentos" :value="$resumo['total']" color="brand" />
        <x-stat label="Pendentes" :value="$resumo['pendentes']" color="amber" />
        <x-stat label="Conciliados" :value="$resumo['conciliados']" color="green" />
        <x-stat label="Ignorados" :value="$resumo['ignorados']" color="gray" />
    </div>

    <x-card :padding="false">
        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pagador</th>
                        <th>Morador</th>
                        <th class="text-right">Valor</th>
                        <th>Sugestão / Baixa</th>
                        <th class="w-32 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($itens as $i)
                        @php
                            $r = $i['r'];
                            $sug = $i['sug'];
                            $status = $sug['status'] ?? null;
                            $pmap = $sug ? $sug['parcelas']->keyBy('id') : collect();
                            $rowClass = match (true) {
                                $r->status === 'CONCILIADO' => 'border-l-4 border-emerald-400',
                                $r->status === 'IGNORADO' => 'border-l-4 border-gray-300 opacity-70',
                                $status === 'ok' => 'border-l-4 border-emerald-400',
                                $status === 'acordo' => 'border-l-4 border-purple-400',
                                default => 'border-l-4 border-amber-400',
                            };
                        @endphp
                        <tr wire:key="rec-{{ $r->id }}" class="{{ $rowClass }}">
                            <td class="whitespace-nowrap text-gray-500">{{ optional($r->data)->format('d/m/Y') }}</td>
                            <td>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $r->nome_pagador ?? '—' }}</div>
                                <div class="text-xs text-gray-400">
                                    <x-badge color="blue" class="mr-1">{{ $r->tipo }}</x-badge>
                                    @if ($r->cpf_pagador)CPF {{ $r->cpf_pagador }}@endif
                                </div>
                            </td>
                            <td>
                                @if ($r->usuario)
                                    <span class="font-medium text-gray-800 dark:text-gray-100">{{ $r->usuario->nome }}</span>
                                @else
                                    <span class="text-amber-600">❓ não identificado</span>
                                @endif
                            </td>
                            <td class="text-right font-semibold">{{ \App\Support\Valor::br($r->valor) }}</td>
                            <td class="text-sm">
                                @if ($r->status === 'CONCILIADO')
                                    @if ($r->id_acordo)
                                        <span class="text-purple-600">Acordo #{{ $r->id_acordo }} quitado/abatido</span>
                                    @else
                                        <span class="text-emerald-700 dark:text-emerald-400">Baixado:
                                            @foreach ($r->parcelas as $p){{ \App\Support\Referencia::abreviada($p->referencia) }}@if (!$loop->last), @endif @endforeach
                                        </span>
                                        @if ($r->valor_credito > 0)
                                            <div class="text-xs text-gray-400">+ crédito {{ \App\Support\Valor::moeda($r->valor_credito) }}</div>
                                        @endif
                                    @endif
                                @elseif ($r->status === 'IGNORADO')
                                    <span class="text-gray-400">Ignorado{{ $r->observacao ? ' — '.$r->observacao : '' }}</span>
                                @elseif ($status === 'acordo')
                                    <span class="text-purple-600">Quita acordo (saldo {{ \App\Support\Valor::moeda($sug['acordo']->saldo) }})</span>
                                @elseif ($status === 'ok')
                                    <span class="text-emerald-700 dark:text-emerald-400">
                                        @foreach ($sug['alocacao']['aplicacoes'] as $ap){{ \App\Support\Referencia::abreviada(optional($pmap->get($ap['id']))->referencia) }}@if (!$loop->last) + @endif @endforeach
                                    </span>
                                @elseif ($status === 'sem_morador')
                                    <span class="text-amber-600">Morador não identificado</span>
                                @else
                                    <span class="text-amber-600">
                                        @if ($sug['alocacao']['tem_parcial']) Pagamento parcial
                                        @elseif ($sug['alocacao']['credito'] > 0) Sobra {{ \App\Support\Valor::moeda($sug['alocacao']['credito']) }}
                                        @else Revisar @endif
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    @if ($r->isPendente())
                                        @if (in_array($status, ['ok', 'acordo'], true))
                                            <button class="btn-ghost p-2 text-emerald-600" title="Confirmar baixa"
                                                    wire:click="confirmar({{ $r->id }})">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                            </button>
                                        @endif
                                        <button class="btn-ghost p-2 text-brand-600" title="Ajustar" wire:click="abrirAjuste({{ $r->id }})">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.929.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.424.07-.764.383-.93.78-.164.398-.142.854.108 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.893c-.09.543-.56.94-1.11.94h-1.094c-.55 0-1.019-.397-1.11-.94l-.148-.893c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.893-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.764-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        </button>
                                        <button class="btn-ghost p-2 text-gray-400" title="Ignorar"
                                                wire:click="ignorar({{ $r->id }})" wire:confirm="Ignorar este recebimento?">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    @else
                                        <button class="btn-ghost p-2 text-gray-500" title="Desfazer / reabrir"
                                                wire:click="desfazer({{ $r->id }})"
                                                wire:confirm="Desfazer esta conciliação? As parcelas voltam a ABERTO.">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-gray-400">Este extrato não tem créditos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- ───────────────────────── Painel de ajuste ───────────────────────── --}}
    <x-modal model="mostrarAjuste" title="Ajustar baixa" maxWidth="2xl">
        @if ($mostrarAjuste)
            @php $rec = $itens->firstWhere('r.id', $ajusteId)['r'] ?? null; @endphp
            @if ($rec)
                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-900/40">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $rec->nome_pagador ?? 'Pagador não informado' }}</div>
                            <div class="text-xs text-gray-400">{{ optional($rec->data)->format('d/m/Y') }} · {{ $rec->tipo }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-400">Recebido</div>
                            <div class="text-lg font-bold text-brand-600">{{ \App\Support\Valor::moeda($rec->valor) }}</div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('ajusteAcao', 'parcelas')"
                                @class(['btn-secondary flex-1', 'ring-2 ring-brand-500' => $ajusteAcao === 'parcelas'])>Aplicar em parcelas</button>
                        <button type="button" wire:click="$set('ajusteAcao', 'ignorar')"
                                @class(['btn-secondary flex-1', 'ring-2 ring-brand-500' => $ajusteAcao === 'ignorar'])>Ignorar</button>
                    </div>

                    @if ($ajusteAcao === 'ignorar')
                        <x-field label="Motivo (opcional)">
                            <textarea wire:model="ajusteMotivo" class="form-textarea" rows="2" placeholder="Ex.: depósito não identificado, transferência entre contas…"></textarea>
                        </x-field>
                    @else
                        <x-field label="Morador">
                            <x-select wire:model.live="ajusteMoradorId">
                                <option value="">— selecione —</option>
                                @foreach ($moradores as $m)
                                    <option value="{{ $m->id }}">{{ $m->nome }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        @if ($ajusteMoradorId)
                            <div>
                                <p class="form-label">Parcelas em aberto (mais antiga primeiro)</p>
                                <div class="max-h-64 space-y-1 overflow-y-auto rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                                    @forelse ($ajusteParcelasInfo as $p)
                                        <label wire:key="ajp-{{ $p->id }}"
                                               class="flex cursor-pointer items-center justify-between rounded-md px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                            <span class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" wire:model.live="ajusteParcelas.{{ $p->id }}"
                                                       class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                <span class="font-mono">{{ \App\Support\Referencia::abreviada($p->referencia) }}</span>
                                                @if ($p->esta_vencido)<x-badge color="red">vencida</x-badge>@endif
                                            </span>
                                            <span class="font-semibold">{{ \App\Support\Valor::moeda($p->saldo_aberto) }}</span>
                                        </label>
                                    @empty
                                        <p class="px-2 py-4 text-center text-sm text-gray-400">Sem parcelas em aberto para este morador.</p>
                                    @endforelse
                                </div>
                            </div>

                            @if ($ajusteResumo)
                                <div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-900/40">
                                    <div class="flex justify-between"><span>Recebido</span><span class="font-semibold">{{ \App\Support\Valor::moeda($ajusteResumo['recebido']) }}</span></div>
                                    <div class="flex justify-between"><span>Selecionado</span><span class="font-semibold">{{ \App\Support\Valor::moeda($ajusteResumo['selecionado']) }}</span></div>
                                    <div class="mt-1 flex justify-between border-t border-gray-200 pt-1 dark:border-gray-700">
                                        @if ($ajusteResumo['diferenca'] > 0.001)
                                            <span class="text-amber-600">Sobra (vira crédito)</span>
                                            <span class="font-bold text-amber-600">{{ \App\Support\Valor::moeda($ajusteResumo['diferenca']) }}</span>
                                        @elseif ($ajusteResumo['diferenca'] < -0.001)
                                            <span class="text-blue-600">Pagamento parcial (falta)</span>
                                            <span class="font-bold text-blue-600">{{ \App\Support\Valor::moeda(abs($ajusteResumo['diferenca'])) }}</span>
                                        @else
                                            <span class="text-emerald-600">Casou exato</span>
                                            <span class="font-bold text-emerald-600">✓</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endif

                    <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-700">
                        <button type="button" class="btn-secondary" wire:click="fecharAjuste">Cancelar</button>
                        <button type="button" class="btn-primary" wire:click="salvarAjuste">Confirmar</button>
                    </div>
                </div>
            @endif
        @endif
    </x-modal>
</div>

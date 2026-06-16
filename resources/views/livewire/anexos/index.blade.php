<div>
    <x-page-header title="Anexos" subtitle="Documentos da associação e dos moradores" />

    @if ($isAdmin)
        <x-card title="Enviar anexos" class="mb-6">
            <form wire:submit="enviar" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-field label="Tipo" name="type_anex">
                        <x-select wire:model.live="type_anex" name="type_anex">
                            <option value="{{ \App\Models\Anexo::TIPO_ASSOCIACAO }}">Associação</option>
                            <option value="{{ \App\Models\Anexo::TIPO_MORADOR }}">Morador</option>
                            <option value="{{ \App\Models\Anexo::TIPO_PRESTACAO_CONTAS }}">Prestação de Contas</option>
                        </x-select>
                    </x-field>
                    @if ((int) $type_anex === \App\Models\Anexo::TIPO_MORADOR)
                        <x-field label="Morador" name="id_morador">
                            <x-select wire:model="id_morador" name="id_morador">
                                <option value="">Selecione…</option>
                                @foreach ($moradores as $m)<option value="{{ $m->id }}">{{ $m->nome }}</option>@endforeach
                            </x-select>
                        </x-field>
                    @endif
                    <x-field label="Assunto" name="subject">
                        <x-input wire:model="subject" name="subject" placeholder="Opcional" />
                    </x-field>
                </div>
                <x-field label="Arquivos" name="arquivos" hint="Pode selecionar vários. Até 10 MB cada.">
                    <input type="file" wire:model="arquivos" multiple class="form-input">
                </x-field>
                @error('arquivos.*')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary">
                        <span wire:loading.remove wire:target="enviar,arquivos">Enviar</span>
                        <span wire:loading wire:target="enviar,arquivos">Enviando…</span>
                    </button>
                </div>
            </form>
        </x-card>
    @endif

    <x-card :padding="false">
        <div class="grid grid-cols-1 gap-3 border-b border-gray-100 p-4 dark:border-gray-700 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Buscar por nome ou assunto…">
            </div>
            <select wire:model.live="tipo" class="form-select">
                <option value="">Todos os tipos</option>
                <option value="{{ \App\Models\Anexo::TIPO_ASSOCIACAO }}">Associação</option>
                <option value="{{ \App\Models\Anexo::TIPO_MORADOR }}">Morador</option>
                <option value="{{ \App\Models\Anexo::TIPO_PRESTACAO_CONTAS }}">Prestação de Contas</option>
            </select>
        </div>

        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Tipo</th>
                        <th>Vínculo</th>
                        <th>Enviado em</th>
                        <th class="w-28 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @php $tipos = [1 => 'Associação', 2 => 'Morador', 3 => 'Prestação de Contas']; @endphp
                    @forelse ($anexos as $a)
                        <tr wire:key="anx-{{ $a->id }}">
                            <td>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $a->original_name }}</div>
                                @if ($a->subject)<div class="text-xs text-gray-400">{{ $a->subject }}</div>@endif
                            </td>
                            <td><x-badge :color="$a->type_anex == 2 ? 'blue' : ($a->type_anex == 3 ? 'purple' : 'gray')">{{ $tipos[$a->type_anex] ?? '—' }}</x-badge></td>
                            <td class="text-gray-500">{{ $a->morador?->nome ?? $a->funcionario?->nome_completo ?? '—' }}</td>
                            <td class="text-gray-500">{{ optional($a->created_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('anexos.download', $a) }}" target="_blank" class="btn-ghost p-2" title="Abrir">
                                        <svg class="h-4 w-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    </a>
                                    @if ($isAdmin || (int) $a->id_morador === (int) auth()->id())
                                        <button class="btn-ghost p-2 text-red-500" wire:click="excluir({{ $a->id }})"
                                                wire:confirm="Excluir este anexo?" title="Excluir">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-gray-400">Nenhum anexo encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($anexos->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $anexos->links() }}</div>
        @endif
    </x-card>
</div>

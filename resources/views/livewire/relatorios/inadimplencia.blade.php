<div>
    <x-page-header title="Inadimplência" :subtitle="$r['label']">
        <x-slot:actions>
            <a href="{{ route('relatorios.index') }}" wire:navigate class="btn-secondary no-print">Relatórios</a>
            <button onclick="window.print()" class="btn-secondary no-print">Imprimir</button>
        </x-slot:actions>
    </x-page-header>

    <x-filtro-periodo :meses="$meses" :modoPeriodo="$modoPeriodo">
        <x-slot:filtros>
            <div>
                <label class="form-label">Quadra</label>
                <select wire:model.live="quadra" class="form-select w-auto">
                    <option value="">Todas</option>
                    @foreach ($quadras as $q)<option value="{{ $q }}">{{ $q }}</option>@endforeach
                </select>
            </div>
        </x-slot:filtros>
    </x-filtro-periodo>

    <div class="mb-4">
        <x-stat label="Total em aberto" :value="\App\Support\Valor::moeda($total)" color="red"
                :sub="$inadimplentes->count().' lançamento(s)'" />
    </div>

    <x-card :padding="false">
        <div class="table-wrap border-0">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Morador</th><th>Telefone</th><th>Quadra</th><th>Casa</th>
                        <th>Vencimento</th><th>Situação</th><th class="text-right">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inadimplentes as $i)
                        <tr wire:key="inad-{{ $loop->index }}">
                            <td class="font-medium text-gray-900 dark:text-white">{{ $i->nome }}</td>
                            <td>
                                @if ($i->telefone)
                                    <a href="https://wa.me/55{{ preg_replace('/\D/', '', $i->telefone) }}" target="_blank" class="text-brand-600 hover:underline">{{ $i->telefone }}</a>
                                @else — @endif
                            </td>
                            <td>{{ $i->quadra }}</td>
                            <td>{{ $i->numero }}</td>
                            <td class="text-gray-500">{{ optional($i->data_vencimento ? \Illuminate\Support\Carbon::parse($i->data_vencimento) : null)->format('d/m/Y') ?? '—' }}</td>
                            <td><x-badge :situacao="$i->situacao" /></td>
                            <td class="text-right font-semibold text-red-600 dark:text-red-400">{{ \App\Support\Valor::br($i->valor) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-gray-400">Nenhuma inadimplência no período. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

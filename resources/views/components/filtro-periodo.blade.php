@props(['meses' => [], 'modoPeriodo' => false])

<x-card class="mb-6 no-print">
    <div class="flex flex-wrap items-end gap-4">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300">
            <input type="checkbox" wire:model.live="modoPeriodo" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            Intervalo de meses
        </label>

        @if ($modoPeriodo)
            <div>
                <label class="form-label">De</label>
                <select wire:model.live="referencia_inicio" class="form-select w-auto">
                    @foreach ($meses as $cod => $label)<option value="{{ $cod }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Até</label>
                <select wire:model.live="referencia_fim" class="form-select w-auto">
                    @foreach ($meses as $cod => $label)<option value="{{ $cod }}">{{ $label }}</option>@endforeach
                </select>
            </div>
        @else
            <div>
                <label class="form-label">Mês de referência</label>
                <select wire:model.live="referencia" class="form-select w-auto">
                    @foreach ($meses as $cod => $label)<option value="{{ $cod }}">{{ $label }}</option>@endforeach
                </select>
            </div>
        @endif

        {{ $filtros ?? '' }}

        @isset($actions)
            <div class="ml-auto flex items-end gap-2">{{ $actions }}</div>
        @endisset
    </div>
</x-card>

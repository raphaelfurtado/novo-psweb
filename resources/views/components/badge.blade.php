@props(['color' => 'gray', 'situacao' => null])

@php
    // Mapeamento por situação de pagamento/saída
    if ($situacao) {
        $color = match (strtoupper($situacao)) {
            'PAGO' => 'green',
            'ABERTO' => 'blue',
            'PENDENTE' => 'amber',
            'CANCELADO' => 'red',
            'ISENTO' => 'purple',
            default => 'gray',
        };
    }

    $palette = [
        'gray'   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        'green'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'blue'   => 'bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300',
        'amber'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'red'    => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.($palette[$color] ?? $palette['gray'])]) }}>
    {{ $situacao ? strtoupper($situacao) : $slot }}
</span>

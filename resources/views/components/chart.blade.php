@props(['options' => []])

{{-- Recria o gráfico quando o nó é substituído (use wire:key com a dimensão que muda). --}}
<div
    x-data="{
        chart: null,
        init() {
            this.chart = new ApexCharts(this.$refs.canvas, @js($options));
            this.chart.render();
        },
        destroy() {
            if (this.chart) this.chart.destroy();
        }
    }"
    {{ $attributes }}
>
    <div x-ref="canvas"></div>
</div>

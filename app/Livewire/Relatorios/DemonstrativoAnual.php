<?php

namespace App\Livewire\Relatorios;

use App\Models\Pagamento;
use App\Models\Saida;
use App\Support\Referencia;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

class DemonstrativoAnual extends Component
{
    #[Url]
    public string $ano = '';

    public function mount(): void
    {
        $this->ano = $this->ano ?: Carbon::now()->format('Y');
    }

    public function render()
    {
        $entradasMes = Pagamento::query()->pago()->entrada()
            ->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$this->ano])
            ->selectRaw('SUBSTR(referencia, 1, 2) as mes, SUM(valor) as total')
            ->groupBy('mes')->pluck('total', 'mes')->toArray();

        $saidasMes = Saida::query()
            ->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$this->ano])
            ->selectRaw('SUBSTR(referencia, 1, 2) as mes, SUM(valor) as total')
            ->groupBy('mes')->pluck('total', 'mes')->toArray();

        $linhas = [];
        $acc = 0;
        $entradasSerie = [];
        $saidasSerie = [];
        $saldoSerie = [];

        foreach (Referencia::MESES as $cod => $nome) {
            $entrada = (float) ($entradasMes[$cod] ?? 0);
            $saida = (float) ($saidasMes[$cod] ?? 0);
            $saldoMes = $entrada - $saida;
            $acc += $saldoMes;

            $linhas[] = [
                'mes' => ucfirst(mb_strtolower($nome)),
                'entrada' => $entrada,
                'saida' => $saida,
                'saldo' => $saldoMes,
                'acumulado' => $acc,
            ];
            $entradasSerie[] = $entrada;
            $saidasSerie[] = $saida;
            $saldoSerie[] = round($acc, 2);
        }

        $chart = [
            'chart' => ['type' => 'line', 'height' => 340, 'fontFamily' => 'inherit', 'foreColor' => '#94a3b8', 'toolbar' => ['show' => false]],
            'series' => [
                ['name' => 'Entradas', 'type' => 'column', 'data' => $entradasSerie],
                ['name' => 'Saídas', 'type' => 'column', 'data' => $saidasSerie],
                ['name' => 'Saldo acumulado', 'type' => 'line', 'data' => $saldoSerie],
            ],
            'stroke' => ['width' => [0, 0, 3], 'curve' => 'smooth'],
            'colors' => ['#10b981', '#ef4444', '#1d66f1'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
            'xaxis' => ['categories' => array_values(Referencia::MESES_ABREV)],
            'dataLabels' => ['enabled' => false],
            'legend' => ['position' => 'top'],
            'grid' => ['borderColor' => 'rgba(148,163,184,0.18)', 'strokeDashArray' => 4],
        ];

        return view('livewire.relatorios.demonstrativo-anual', [
            'ano' => $this->ano,
            'anos' => $this->anosDisponiveis(),
            'linhas' => $linhas,
            'totalEntradas' => array_sum($entradasSerie),
            'totalSaidas' => array_sum($saidasSerie),
            'chart' => $chart,
        ]);
    }

    protected function anosDisponiveis(): array
    {
        $anos = Pagamento::query()->selectRaw('DISTINCT SUBSTR(referencia, 3, 4) as ano')
            ->orderByDesc('ano')->pluck('ano')->filter()->values()->all();
        $atual = Carbon::now()->format('Y');
        if (! in_array($atual, $anos)) {
            array_unshift($anos, $atual);
        }

        return $anos;
    }
}

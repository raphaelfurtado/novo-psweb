<?php

namespace App\Livewire\Dashboard;

use App\Models\Pagamento;
use App\Models\Saida;
use App\Models\TipoPagamento;
use App\Support\Referencia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    #[Url]
    public string $ano = '';

    #[Url]
    public string $ref = '';

    public function mount(): void
    {
        $this->ano = $this->ano ?: Carbon::now()->format('Y');
        $this->ref = $this->ref ?: Carbon::now()->format('mY');
    }

    /** Mapeia um pluck ['01'=>v,...] para um array de 12 posições (jan..dez). */
    protected function serieMensal(array $porMes): array
    {
        $serie = [];
        for ($m = 1; $m <= 12; $m++) {
            $serie[] = (float) ($porMes[sprintf('%02d', $m)] ?? 0);
        }

        return $serie;
    }

    public function render()
    {
        $ano = $this->ano;

        // Se for morador, mostra um dashboard simplificado.
        if (! auth()->user()->isAdmin()) {
            return view('livewire.dashboard.morador', $this->dadosMorador());
        }

        // Séries mensais (entradas reais x saídas)
        $entradasMes = Pagamento::query()->pago()->entrada()
            ->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])
            ->selectRaw('SUBSTR(referencia, 1, 2) as mes, SUM(valor) as total')
            ->groupBy('mes')->pluck('total', 'mes')->toArray();

        $saidasMes = Saida::query()
            ->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])
            ->selectRaw('SUBSTR(referencia, 1, 2) as mes, SUM(valor) as total')
            ->groupBy('mes')->pluck('total', 'mes')->toArray();

        $entradas = $this->serieMensal($entradasMes);
        $saidas = $this->serieMensal($saidasMes);

        $saldoAcumulado = [];
        $acc = 0;
        foreach ($entradas as $i => $e) {
            $acc += $e - $saidas[$i];
            $saldoAcumulado[] = round($acc, 2);
        }

        // Receita por categoria (ano, PAGO)
        $categoria = Pagamento::query()->pago()
            ->whereRaw('SUBSTR(pagamento.referencia, 3, 4) = ?', [$ano])
            ->join('tipo_pagamento', 'tipo_pagamento.codigo', '=', 'pagamento.id_tipo_pagamento')
            ->selectRaw('tipo_pagamento.descricao, SUM(pagamento.valor) as total')
            ->groupBy('tipo_pagamento.codigo', 'tipo_pagamento.descricao')
            ->orderByDesc('total')->get();

        // KPIs por tipo (ano)
        $kpis = Pagamento::query()
            ->whereRaw('SUBSTR(pagamento.referencia, 3, 4) = ?', [$ano])
            ->join('tipo_pagamento', 'tipo_pagamento.codigo', '=', 'pagamento.id_tipo_pagamento')
            ->selectRaw("tipo_pagamento.codigo, tipo_pagamento.descricao,
                SUM(CASE WHEN situacao = 'PAGO' THEN valor ELSE 0 END) as pago,
                SUM(CASE WHEN situacao IN ('ABERTO','PENDENTE') THEN valor ELSE 0 END) as aberto,
                SUM(CASE WHEN situacao = 'PAGO' THEN 1 ELSE 0 END) as qtd_pago,
                COUNT(*) as qtd")
            ->groupBy('tipo_pagamento.codigo', 'tipo_pagamento.descricao')
            ->orderBy('tipo_pagamento.codigo')->get();

        // Caixa da referência selecionada
        $caixa = $this->resumoCaixa($this->ref, TipoPagamento::TIPOS_ENTRADA);
        $caixaConcreto = $this->resumoCaixa($this->ref, [TipoPagamento::TIPO_CONCRETO]);

        // Inadimplência (mensalidade no ano)
        $mensAberto = (float) Pagamento::emAberto()->where('id_tipo_pagamento', TipoPagamento::TIPO_MENSALIDADE)
            ->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])->sum('valor');
        $mensTotal = (float) Pagamento::where('id_tipo_pagamento', TipoPagamento::TIPO_MENSALIDADE)
            ->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])->sum('valor');
        $percInadimplencia = $mensTotal > 0 ? round($mensAberto / $mensTotal * 100, 1) : 0;

        $mesesAbrev = array_values(Referencia::MESES_ABREV);

        return view('livewire.dashboard.index', [
            'anos' => $this->anosDisponiveis(),
            'referencias' => $this->referenciasCaixa(),
            'totalEntradasAno' => array_sum($entradas),
            'totalSaidasAno' => array_sum($saidas),
            'saldoAno' => array_sum($entradas) - array_sum($saidas),
            'caixa' => $caixa,
            'caixaConcreto' => $caixaConcreto,
            'kpis' => $kpis,
            'percInadimplencia' => $percInadimplencia,
            'mensAberto' => $mensAberto,
            'chartFluxo' => $this->optBarras($mesesAbrev, $entradas, $saidas),
            'chartSaldo' => $this->optArea($mesesAbrev, $saldoAcumulado),
            'chartCategoria' => $this->optDonut($categoria),
        ]);
    }

    protected function resumoCaixa(string $ref, array $tipos): array
    {
        $entrada = (float) Pagamento::pago()->whereIn('id_tipo_pagamento', $tipos)
            ->where('referencia', $ref)->sum('valor');

        $saidaQ = Saida::where('referencia', $ref);
        if ($tipos === [TipoPagamento::TIPO_CONCRETO]) {
            $saidaQ->where('id_tipo_pagamento', TipoPagamento::TIPO_CONCRETO);
        }
        $saida = (float) $saidaQ->sum('valor');

        return ['entrada' => $entrada, 'saida' => $saida, 'saldo' => $entrada - $saida];
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

    protected function referenciasCaixa(): array
    {
        $refs = DB::table('pagamento')->select('referencia')->distinct()
            ->union(DB::table('saida')->select('referencia')->distinct())
            ->pluck('referencia')->filter()->all();

        $refs[] = Carbon::now()->format('mY');
        $refs = array_unique($refs);

        usort($refs, fn ($a, $b) => Referencia::ordenavel($b) <=> Referencia::ordenavel($a));

        return collect($refs)->mapWithKeys(fn ($r) => [$r => Referencia::abreviada($r)])->all();
    }

    protected function dadosMorador(): array
    {
        $id = auth()->id();
        $ano = $this->ano;

        return [
            'totalPago' => (float) Pagamento::where('id_usuario', $id)->where('situacao', 'PAGO')
                ->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])->sum('valor'),
            'totalAberto' => (float) Pagamento::where('id_usuario', $id)->whereIn('situacao', ['ABERTO', 'PENDENTE'])
                ->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])->sum('valor'),
            'proximos' => Pagamento::where('id_usuario', $id)->whereIn('situacao', ['ABERTO', 'PENDENTE'])
                ->with('tipoPagamento')->orderBy('data_vencimento')->limit(6)->get(),
            'anos' => $this->anosDisponiveis(),
        ];
    }

    // ---- Opções dos gráficos (ApexCharts) ----

    protected function baseChart(string $type, int $height = 320): array
    {
        return [
            'chart' => ['type' => $type, 'height' => $height, 'fontFamily' => 'inherit',
                'foreColor' => '#94a3b8', 'toolbar' => ['show' => false], 'animations' => ['enabled' => true]],
            'grid' => ['borderColor' => 'rgba(148,163,184,0.18)', 'strokeDashArray' => 4],
            'dataLabels' => ['enabled' => false],
        ];
    }

    protected function optBarras(array $cats, array $entradas, array $saidas): array
    {
        return array_merge($this->baseChart('bar'), [
            'series' => [
                ['name' => 'Entradas', 'data' => $entradas],
                ['name' => 'Saídas', 'data' => $saidas],
            ],
            'xaxis' => ['categories' => $cats],
            'colors' => ['#10b981', '#ef4444'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '60%']],
            'legend' => ['position' => 'top', 'horizontalAlign' => 'right'],
        ]);
    }

    protected function optArea(array $cats, array $saldo): array
    {
        return array_merge($this->baseChart('area'), [
            'series' => [['name' => 'Saldo acumulado', 'data' => $saldo]],
            'xaxis' => ['categories' => $cats],
            'colors' => ['#1d66f1'],
            'stroke' => ['curve' => 'smooth', 'width' => 2.5],
            'fill' => ['type' => 'gradient', 'gradient' => ['shadeIntensity' => 1, 'opacityFrom' => 0.35, 'opacityTo' => 0.05]],
        ]);
    }

    protected function optDonut($categoria): array
    {
        return array_merge($this->baseChart('donut'), [
            'series' => $categoria->pluck('total')->map(fn ($v) => round((float) $v, 2))->all(),
            'labels' => $categoria->pluck('descricao')->all(),
            'colors' => ['#1d66f1', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899'],
            'legend' => ['position' => 'bottom'],
            'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
        ]);
    }
}

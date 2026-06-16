<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Models\Pagamento;
use App\Models\Saida;
use App\Models\User;
use App\Services\RelatorioService;
use App\Support\Referencia;
use App\Support\Valor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RelatorioPdfController extends Controller
{
    public function __construct(protected RelatorioService $service) {}

    /** Resolve o período (mês único ou intervalo) a partir da query string. */
    protected function periodo(Request $request): array
    {
        $modo = (bool) $request->boolean('modoPeriodo');
        $inicio = $request->string('referencia_inicio')->toString();
        $fim = $request->string('referencia_fim')->toString();
        $ref = $request->string('referencia')->toString() ?: Referencia::atual();

        if ($modo && $inicio && $fim) {
            return ['inicio' => $inicio, 'fim' => $fim,
                'label' => Referencia::porExtenso($inicio) . ' até ' . Referencia::porExtenso($fim)];
        }

        return ['inicio' => $ref, 'fim' => $ref, 'label' => Referencia::porExtenso($ref)];
    }

    public function pdf(Request $request, string $relatorio)
    {
        $p = $this->periodo($request);

        $config = match ($relatorio) {
            'prestacao-contas' => [
                'view' => 'pdf.prestacao-contas',
                'data' => $this->service->prestacaoContas($p['inicio'], $p['fim']),
                'file' => 'prestacao_contas',
            ],
            'receitas-categoria' => [
                'view' => 'pdf.receitas-categoria',
                'data' => $this->dadosReceitasCategoria($p),
                'file' => 'receitas_categoria',
            ],
            'resumo-caixa' => [
                'view' => 'pdf.resumo-caixa',
                'data' => $this->service->resumoCaixa($p['inicio'], $p['fim']),
                'file' => 'resumo_caixa',
            ],
            'extrato-morador' => $this->dadosExtrato($request),
            default => abort(404),
        };

        $data = array_merge($config['data'], ['label' => $config['label'] ?? $p['label']]);

        $pdf = Pdf::loadView($config['view'], $data)->setPaper('a4');

        return $pdf->stream($config['file'] . '.pdf');
    }

    public function excel(Request $request, string $relatorio)
    {
        $p = $this->periodo($request);

        [$rows, $headings, $title, $file] = match ($relatorio) {
            'prestacao-contas' => $this->excelPrestacao($p),
            'receitas-categoria' => $this->excelReceitas($p),
            'resumo-caixa' => $this->excelResumo($p),
            'demonstrativo-anual' => $this->excelDemonstrativo($request),
            'inadimplencia-avancada' => $this->excelInadimplencia($request),
            default => abort(404),
        };

        return Excel::download(new ArrayExport($rows, $headings, $title), $file . '.xlsx');
    }

    // ---------- PDF helpers ----------

    protected function dadosReceitasCategoria(array $p): array
    {
        $agrupado = $this->service->receitasCategoria($p['inicio'], $p['fim']);

        return [
            'agrupado' => $agrupado,
            'totalGeral' => (float) $agrupado->sum('total'),
            'qtdGeral' => (int) $agrupado->sum('quantidade'),
        ];
    }

    protected function dadosExtrato(Request $request): array
    {
        $morador = User::with('endereco')->findOrFail($request->integer('usuario'));
        $ano = $request->string('ano')->toString() ?: now()->format('Y');

        $pagamentos = Pagamento::where('id_usuario', $morador->id)
            ->when($ano, fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano]))
            ->with(['tipoPagamento', 'formaPagamento'])
            ->orderByDesc('referencia')->get();

        return [
            'view' => 'pdf.extrato-morador',
            'file' => 'extrato_' . str($morador->nome)->slug('_'),
            'label' => 'Ano ' . $ano,
            'data' => [
                'morador' => $morador,
                'pagamentos' => $pagamentos,
                'totalPago' => (float) $pagamentos->where('situacao', 'PAGO')->sum('valor'),
                'totalAberto' => (float) $pagamentos->whereIn('situacao', ['ABERTO', 'PENDENTE'])->sum('valor'),
            ],
        ];
    }

    // ---------- Excel helpers ----------

    protected function excelPrestacao(array $p): array
    {
        $d = $this->service->prestacaoContas($p['inicio'], $p['fim']);
        $rows = [];
        foreach ($d['despesas'] as $x) {
            $rows[] = ['DESPESA', optional($x->data_pagamento)->format('d/m/Y'), $x->favorecido, $x->categoria, $x->observacao, Valor::br($x->valor)];
        }
        foreach ($d['receitas'] as $x) {
            $rows[] = ['RECEITA', optional($x->data_pagamento)->format('d/m/Y'), $x->nome, $x->categoria, $x->observacao, Valor::br($x->valor)];
        }

        return [$rows, ['Tipo', 'Data', 'Nome/Favorecido', 'Categoria', 'Observação', 'Valor'], 'Prestação de Contas', 'prestacao_contas'];
    }

    protected function excelReceitas(array $p): array
    {
        $rows = $this->service->receitasCategoria($p['inicio'], $p['fim'])
            ->map(fn ($a) => [$a->codigo, $a->descricao, $a->quantidade, Valor::br($a->total)])->all();

        return [$rows, ['Código', 'Categoria', 'Lançamentos', 'Total'], 'Receitas por Categoria', 'receitas_categoria'];
    }

    protected function excelResumo(array $p): array
    {
        $d = $this->service->resumoCaixa($p['inicio'], $p['fim']);
        $rows = $d['saidasDetalhe']->map(fn ($s) => [optional($s->data_pagamento)->format('d/m/Y'), $s->categoria, $s->observacao, Valor::br($s->valor)])->all();

        return [$rows, ['Data', 'Categoria', 'Observação', 'Valor'], 'Resumo de Caixa', 'resumo_caixa'];
    }

    protected function excelDemonstrativo(Request $request): array
    {
        $ano = $request->string('ano')->toString() ?: now()->format('Y');

        $entradas = Pagamento::query()->pago()->entrada()->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])
            ->selectRaw('SUBSTR(referencia, 1, 2) as mes, SUM(valor) as total')->groupBy('mes')->pluck('total', 'mes')->toArray();
        $saidas = Saida::query()->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])
            ->selectRaw('SUBSTR(referencia, 1, 2) as mes, SUM(valor) as total')->groupBy('mes')->pluck('total', 'mes')->toArray();

        $rows = [];
        $acc = 0;
        foreach (Referencia::MESES as $cod => $nome) {
            $e = (float) ($entradas[$cod] ?? 0);
            $s = (float) ($saidas[$cod] ?? 0);
            $acc += $e - $s;
            $rows[] = [$nome, Valor::br($e), Valor::br($s), Valor::br($e - $s), Valor::br($acc)];
        }

        return [$rows, ['Mês', 'Entradas', 'Saídas', 'Saldo do mês', 'Acumulado'], "Demonstrativo {$ano}", "demonstrativo_{$ano}"];
    }

    protected function excelInadimplencia(Request $request): array
    {
        $ano = $request->string('ano')->toString();
        $quadra = $request->string('quadra')->toString();

        $ranking = Pagamento::query()
            ->join('users', 'users.id', '=', 'pagamento.id_usuario')
            ->leftJoin('endereco', 'endereco.id_usuario', '=', 'users.id')
            ->whereIn('pagamento.situacao', ['ABERTO', 'PENDENTE'])
            ->when($ano, fn ($q) => $q->whereRaw('SUBSTR(pagamento.referencia, 3, 4) = ?', [$ano]))
            ->when($quadra !== '', fn ($q) => $q->where('endereco.quadra', $quadra))
            ->selectRaw('users.nome, users.telefone, endereco.quadra, endereco.numero,
                SUM(pagamento.valor) as total_aberto, COUNT(DISTINCT pagamento.referencia) as meses_atraso')
            ->groupBy('users.nome', 'users.telefone', 'endereco.quadra', 'endereco.numero')
            ->orderByDesc('total_aberto')->get();

        $rows = $ranking->map(fn ($d) => [$d->nome, $d->telefone, $d->quadra, $d->numero, $d->meses_atraso, Valor::br($d->total_aberto)])->all();

        return [$rows, ['Morador', 'Telefone', 'Quadra', 'Casa', 'Meses em atraso', 'Total devido'], 'Inadimplência', 'inadimplencia'];
    }
}

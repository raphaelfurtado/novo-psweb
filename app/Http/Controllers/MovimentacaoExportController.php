<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Models\Pagamento;
use App\Models\Saida;
use App\Support\Referencia;
use App\Support\Valor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Exporta as LISTAGENS (com os filtros atuais) em PDF ou Excel.
 * Reaplica os mesmos filtros dos componentes Livewire de cada tela.
 *
 * O PDF (dompdf) é limitado a MAX_PDF_ROWS linhas — tabelas muito grandes
 * estouram a memória; nesse caso o total continua do conjunto completo e há
 * um aviso de truncamento, recomendando o Excel para a lista inteira.
 */
class MovimentacaoExportController extends Controller
{
    private const MAX_PDF_ROWS = 800;

    public function __construct()
    {
        // PDFs de listagens podem ser pesados no dompdf.
        ini_set('memory_limit', '1024M');
    }

    public function pagamentos(Request $r)
    {
        $rows = Pagamento::query()
            ->with(['usuario', 'tipoPagamento', 'formaPagamento'])
            ->when($r->filled('ano'), fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$r->query('ano')]))
            ->when($r->filled('mes'), fn ($q) => $q->whereRaw('SUBSTR(referencia, 1, 2) = ?', [$r->query('mes')]))
            ->when($r->query('situacao') === 'VENCIDO', fn ($q) => $q->vencido())
            ->when($r->filled('situacao') && $r->query('situacao') !== 'VENCIDO',
                fn ($q) => $q->where('situacao', $r->query('situacao')))
            ->when($r->filled('tipo'), fn ($q) => $q->where('id_tipo_pagamento', $r->query('tipo')))
            ->when($r->filled('q'), fn ($q) => $q->whereHas('usuario',
                fn ($q) => $q->where('nome', 'like', '%' . $r->query('q') . '%')))
            ->when($r->filled('quadra'), fn ($q) => $q->whereHas('usuario.endereco',
                fn ($q) => $q->where('quadra', $r->query('quadra'))))
            ->when($r->filled('numero'), fn ($q) => $q->whereHas('usuario.endereco',
                fn ($q) => $q->where('numero', $r->query('numero'))))
            ->orderByRaw('SUBSTR(referencia, 3, 4) ASC, SUBSTR(referencia, 1, 2) ASC')->orderBy('id')->get();

        if ($r->query('fmt') === 'excel') {
            $linhas = $rows->map(fn ($p) => [
                $p->usuario?->nome, Referencia::abreviada($p->referencia), $p->tipoPagamento?->descricao,
                $p->formaPagamento?->descricao, optional($p->data_pagamento)->format('d/m/Y'),
                optional($p->data_vencimento)->format('d/m/Y'), Valor::br($p->valor),
                $p->esta_vencido ? 'VENCIDO' : $p->situacao,
            ])->all();

            return Excel::download(new ArrayExport($linhas,
                ['Morador', 'Referência', 'Tipo', 'Forma', 'Pagamento', 'Vencimento', 'Valor', 'Situação'],
                'Pagamentos'), 'pagamentos.xlsx');
        }

        return $this->pdf('pdf.pagamentos', $rows, $this->label($r), 'pagamentos', 'landscape');
    }

    public function saidas(Request $r)
    {
        $rows = Saida::query()->despesa()
            ->with(['tipoPagamento', 'formaPagamento', 'recebedor'])
            ->when($r->filled('ano'), fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$r->query('ano')]))
            ->when($r->filled('mes'), fn ($q) => $q->whereRaw('SUBSTR(referencia, 1, 2) = ?', [$r->query('mes')]))
            ->when($r->filled('tipo'), fn ($q) => $q->where('id_tipo_pagamento', $r->query('tipo')))
            ->when($r->filled('q'), fn ($q) => $q->where('observacao', 'like', '%' . $r->query('q') . '%'))
            ->orderByDesc('data_pagamento')->orderByDesc('id')->get();

        if ($r->query('fmt') === 'excel') {
            $linhas = $rows->map(fn ($s) => [
                optional($s->data_pagamento)->format('d/m/Y'), $s->tipoPagamento?->descricao,
                $s->recebedor?->nome, $s->observacao, Referencia::abreviada($s->referencia), Valor::br($s->valor),
            ])->all();

            return Excel::download(new ArrayExport($linhas,
                ['Data', 'Categoria', 'Recebedor', 'Observação', 'Referência', 'Valor'], 'Despesas'), 'despesas.xlsx');
        }

        return $this->pdf('pdf.saidas', $rows, $this->label($r), 'despesas');
    }

    public function folha(Request $r)
    {
        $rows = Saida::query()->deFolha()
            ->with(['funcionario', 'tipoSaida'])
            ->when($r->filled('ano'), fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$r->query('ano')]))
            ->when($r->filled('mes'), fn ($q) => $q->whereRaw('SUBSTR(referencia, 1, 2) = ?', [$r->query('mes')]))
            ->when($r->filled('q'), fn ($q) => $q->whereHas('funcionario',
                fn ($q) => $q->where('nome_completo', 'like', '%' . $r->query('q') . '%')))
            ->orderByDesc('data_pagamento')->orderByDesc('id')->get();

        if ($r->query('fmt') === 'excel') {
            $linhas = $rows->map(fn ($p) => [
                $p->funcionario?->nome_completo, $p->tipoSaida?->descricao,
                optional($p->data_pagamento)->format('d/m/Y'), Referencia::abreviada($p->referencia),
                $p->observacao, Valor::br($p->valor),
            ])->all();

            return Excel::download(new ArrayExport($linhas,
                ['Funcionário', 'Tipo', 'Data', 'Referência', 'Observação', 'Valor'], 'Folha'), 'folha.xlsx');
        }

        return $this->pdf('pdf.folha', $rows, $this->label($r), 'folha');
    }

    /** Renderiza o PDF limitando o número de linhas (proteção de memória do dompdf). */
    protected function pdf(string $view, Collection $rows, string $label, string $file, string $orientacao = 'portrait')
    {
        $count = $rows->count();
        $total = (float) $rows->sum('valor');
        $truncado = $count > self::MAX_PDF_ROWS;

        return Pdf::loadView($view, [
            'rows' => $rows->take(self::MAX_PDF_ROWS),
            'count' => $count,
            'total' => $total,
            'truncado' => $truncado,
            'max' => self::MAX_PDF_ROWS,
            'label' => $label,
        ])->setPaper('a4', $orientacao)->stream($file . '.pdf');
    }

    protected function label(Request $r): string
    {
        $parts = [];
        if ($r->filled('ano')) {
            $parts[] = 'Ano ' . $r->query('ano');
        }
        if ($r->filled('mes')) {
            $parts[] = ucfirst(mb_strtolower(Referencia::MESES[$r->query('mes')] ?? ''));
        }
        if ($r->filled('situacao')) {
            $parts[] = $r->query('situacao') === 'VENCIDO' ? 'Vencidos' : $r->query('situacao');
        }
        if ($r->filled('q')) {
            $parts[] = 'Busca: "' . $r->query('q') . '"';
        }

        return implode(' · ', $parts) ?: 'Todos os registros';
    }
}

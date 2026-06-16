<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Exportação genérica para planilha: cabeçalho + linhas já formatadas. */
class ArrayExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        protected array $rows,
        protected array $headings,
        protected string $title = 'Relatório',
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }
}

<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Filtro de período sobre a coluna `referencia` (MMYYYY).
 * Espelha o Relatorio::applyDateRangeFilter do psweb legado, porém com bind parametrizado:
 * STR_TO_DATE(CONCAT('01', referencia), '%d%m%Y') BETWEEN inicio AND fim.
 */
trait FiltraPeriodo
{
    public function scopeNoPeriodo(Builder $query, ?string $inicio, ?string $fim, ?string $coluna = null): Builder
    {
        $coluna ??= $this->getTable() . '.referencia';
        $inicio = $inicio ?: $fim;
        $fim = $fim ?: $inicio;

        if (! $inicio || ! $fim) {
            return $query;
        }

        return $query->whereRaw(
            "STR_TO_DATE(CONCAT('01', $coluna), '%d%m%Y') "
            . "BETWEEN STR_TO_DATE(CONCAT('01', ?), '%d%m%Y') "
            . "AND STR_TO_DATE(CONCAT('01', ?), '%d%m%Y')",
            [$inicio, $fim]
        );
    }
}

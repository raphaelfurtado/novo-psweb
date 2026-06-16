<?php

namespace App\Support;

/** Conversão de valores monetários no formato brasileiro. */
class Valor
{
    /**
     * "R$ 1.234,56" | "1.234,56" | "1234.56" | "1234" => 1234.56
     * Reproduz a limpeza do psweb (remove milhar, converte vírgula decimal),
     * mas também aceita ponto decimal quando não há vírgula.
     */
    public static function paraDecimal(string|float|int|null $valor): ?float
    {
        if ($valor === null) {
            return null;
        }

        $v = trim((string) $valor);
        if ($v === '') {
            return null;
        }

        $v = preg_replace('/[^\d,.\-]/', '', $v);

        if (str_contains($v, ',')) {
            // Formato brasileiro: ponto = milhar, vírgula = decimal.
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }

        return is_numeric($v) ? (float) $v : null;
    }

    /** 1234.56 => "1.234,56" */
    public static function br(string|float|int|null $valor, int $decimais = 2): string
    {
        return number_format((float) $valor, $decimais, ',', '.');
    }

    /** 1234.56 => "R$ 1.234,56" */
    public static function moeda(string|float|int|null $valor): string
    {
        return 'R$ ' . self::br($valor);
    }
}

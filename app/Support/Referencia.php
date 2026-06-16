<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Utilitários para a "referencia" do domínio: string no formato MMYYYY
 * (ex.: "062026" = junho/2026). É a competência mensal usada em pagamentos e saídas.
 */
class Referencia
{
    public const MESES = [
        '01' => 'JANEIRO', '02' => 'FEVEREIRO', '03' => 'MARÇO', '04' => 'ABRIL',
        '05' => 'MAIO', '06' => 'JUNHO', '07' => 'JULHO', '08' => 'AGOSTO',
        '09' => 'SETEMBRO', '10' => 'OUTUBRO', '11' => 'NOVEMBRO', '12' => 'DEZEMBRO',
    ];

    public const MESES_ABREV = [
        '01' => 'JAN', '02' => 'FEV', '03' => 'MAR', '04' => 'ABR',
        '05' => 'MAI', '06' => 'JUN', '07' => 'JUL', '08' => 'AGO',
        '09' => 'SET', '10' => 'OUT', '11' => 'NOV', '12' => 'DEZ',
    ];

    /** Referência do mês atual (MMYYYY). */
    public static function atual(): string
    {
        return Carbon::now()->format('mY');
    }

    public static function mes(string $ref): string
    {
        return substr($ref, 0, 2);
    }

    public static function ano(string $ref): string
    {
        return substr($ref, 2, 4);
    }

    /** "062026" => "JUNHO 2026" */
    public static function porExtenso(?string $ref): string
    {
        if (! $ref || strlen($ref) < 6) {
            return '';
        }

        return (self::MESES[self::mes($ref)] ?? '') . ' ' . self::ano($ref);
    }

    /** "062026" => "JUN/2026" */
    public static function abreviada(?string $ref): string
    {
        if (! $ref || strlen($ref) < 6) {
            return '';
        }

        return (self::MESES_ABREV[self::mes($ref)] ?? '') . '/' . self::ano($ref);
    }

    /** Constrói a referência a partir de mês/ano numéricos. */
    public static function de(int $mes, int $ano): string
    {
        return sprintf('%02d%04d', $mes, $ano);
    }

    /** Data comparável (primeiro dia do mês) no formato Y-m-d. */
    public static function paraData(string $ref): string
    {
        return self::ano($ref) . '-' . self::mes($ref) . '-01';
    }

    /** As 12 referências de um ano (01..12), em ordem. */
    public static function mesesDoAno(int $ano): array
    {
        $refs = [];
        for ($m = 1; $m <= 12; $m++) {
            $refs[] = self::de($m, $ano);
        }

        return $refs;
    }

    /** Chave ordenável YYYYMM a partir de MMYYYY. */
    public static function ordenavel(string $ref): string
    {
        return self::ano($ref) . self::mes($ref);
    }
}

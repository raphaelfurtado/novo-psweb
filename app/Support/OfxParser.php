<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Parser tolerante de arquivos OFX (extrato bancário, ex.: Banco do Brasil).
 *
 * OFX é SGML (tags sem fechamento). Em vez de um parser XML rígido, lemos os
 * blocos <STMTTRN>…</STMTTRN> e os valores de cada tag até o fim da linha.
 * Mantém apenas os CRÉDITOS (entradas), que são o que interessa à conciliação.
 */
class OfxParser
{
    /**
     * @return array{
     *     conta: array{banco:?string, conta:?string, inicio:?Carbon, fim:?Carbon},
     *     transacoes: array<int, array{trntype:string, tipo:string, data:Carbon, valor:float, fitid:string, nome:?string, memo:?string}>
     * }
     */
    public static function parse(string $conteudo): array
    {
        $conteudo = self::normalizarEncoding($conteudo);

        return [
            'conta' => self::extrairInfoConta($conteudo),
            'transacoes' => self::extrairTransacoes($conteudo),
        ];
    }

    private static function normalizarEncoding(string $c): string
    {
        if (mb_check_encoding($c, 'UTF-8')) {
            return $c;
        }

        return mb_convert_encoding($c, 'UTF-8', 'Windows-1252');
    }

    private static function extrairInfoConta(string $c): array
    {
        return [
            'banco' => self::tag('BANKID', $c),
            'conta' => self::tag('ACCTID', $c),
            'inicio' => self::data(self::tag('DTSTART', $c)),
            'fim' => self::data(self::tag('DTEND', $c)),
        ];
    }

    private static function extrairTransacoes(string $c): array
    {
        // Aceita STMTTRN com ou sem tag de fechamento (SGML permite omitir):
        // cada bloco vai até a próxima <STMTTRN>, </STMTTRN> ou </BANKTRANLIST>.
        if (! preg_match_all('/<STMTTRN>(.*?)(?=<STMTTRN>|<\/STMTTRN>|<\/BANKTRANLIST>)/is', $c, $blocos)) {
            return [];
        }

        $transacoes = [];

        foreach ($blocos[1] as $bloco) {
            $valor = Valor::paraDecimal(self::tag('TRNAMT', $bloco));
            $data = self::data(self::tag('DTPOSTED', $bloco));

            // Só créditos (entradas). Débitos e linhas sem valor/data são ignorados.
            if ($valor === null || $valor <= 0 || $data === null) {
                continue;
            }

            $trntype = strtoupper((string) self::tag('TRNTYPE', $bloco));
            $nome = self::tag('NAME', $bloco);
            $memo = self::tag('MEMO', $bloco);

            $transacoes[] = [
                'trntype' => $trntype,
                'tipo' => self::tipoAmigavel($trntype, $nome, $memo),
                'data' => $data,
                'valor' => $valor,
                'fitid' => (string) self::tag('FITID', $bloco),
                'nome' => $nome,
                'memo' => $memo,
            ];
        }

        return $transacoes;
    }

    /** Valor de uma tag OFX (vai até o fim da linha ou a próxima tag). */
    private static function tag(string $tag, string $haystack): ?string
    {
        if (preg_match('/<' . $tag . '>([^<\r\n]*)/i', $haystack, $m)) {
            $v = trim($m[1]);

            return $v === '' ? null : $v;
        }

        return null;
    }

    /** "20260603120000[-3:GMT]" | "20260603" => Carbon (ou null). */
    private static function data(?string $bruto): ?Carbon
    {
        if (! $bruto || ! preg_match('/^(\d{8})/', $bruto, $m)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Ymd', $m[1])->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function tipoAmigavel(string $trntype, ?string $nome, ?string $memo): string
    {
        $texto = strtoupper(($nome ?? '') . ' ' . ($memo ?? ''));

        if (str_contains($texto, 'PIX')) {
            return 'PIX';
        }
        if (str_contains($texto, 'TED') || $trntype === 'XFER') {
            return 'TED';
        }
        if (str_contains($texto, 'DOC')) {
            return 'DOC';
        }

        return $trntype ?: 'CREDITO';
    }
}

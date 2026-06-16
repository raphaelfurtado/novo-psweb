<?php

namespace App\Support;

/**
 * Alocação FIFO de um valor recebido sobre parcelas em aberto.
 *
 * Regra de ouro da conciliação: o dinheiro recebido quita as parcelas da mais
 * antiga para a mais nova. Não se pergunta "de que mês é o pagamento" — abate-se
 * sempre a dívida mais antiga primeiro. Lógica pura (sem banco) para ser testável.
 *
 * Trabalha internamente em centavos (int) para evitar erros de ponto flutuante.
 */
class AlocacaoFifo
{
    /**
     * @param  float  $valor      Valor recebido.
     * @param  array  $parcelas   Lista ordenada (mais antiga primeiro) de
     *                            ['id' => int, 'saldo' => float].
     * @return array{
     *     aplicacoes: array<int, array{id:int, valor:float}>,
     *     total_aplicado: float,
     *     credito: float,
     *     integral: bool,
     *     tem_parcial: bool
     * }
     */
    public static function alocar(float $valor, array $parcelas): array
    {
        $restante = (int) round($valor * 100);
        $aplicacoes = [];
        $temParcial = false;

        foreach ($parcelas as $p) {
            if ($restante <= 0) {
                break;
            }

            $saldo = (int) round(((float) $p['saldo']) * 100);
            if ($saldo <= 0) {
                continue;
            }

            $aplica = min($restante, $saldo);
            $aplicacoes[] = ['id' => (int) $p['id'], 'valor' => $aplica / 100];

            if ($aplica < $saldo) {
                $temParcial = true;
            }

            $restante -= $aplica;
        }

        $credito = $restante / 100;                          // sobra após cobrir tudo (troco)
        $totalAplicado = ((int) round($valor * 100) - $restante) / 100;

        // "integral" = o valor casou exatamente com a soma de N parcelas inteiras,
        // sem parcial e sem troco. É o caso 🟢 (baixa automática segura).
        $integral = ! $temParcial && $credito <= 0.0001 && count($aplicacoes) > 0;

        return [
            'aplicacoes' => $aplicacoes,
            'total_aplicado' => $totalAplicado,
            'credito' => $credito,
            'integral' => $integral,
            'tem_parcial' => $temParcial,
        ];
    }
}

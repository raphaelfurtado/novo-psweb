<?php

namespace Tests\Unit;

use App\Support\AlocacaoFifo;
use PHPUnit\Framework\TestCase;

class AlocacaoFifoTest extends TestCase
{
    private function parcelas(array $saldos): array
    {
        $out = [];
        foreach ($saldos as $i => $s) {
            $out[] = ['id' => $i + 1, 'saldo' => $s];
        }

        return $out;
    }

    public function test_uma_parcela_exata_e_integral(): void
    {
        $r = AlocacaoFifo::alocar(90.0, $this->parcelas([90, 90, 90]));

        $this->assertTrue($r['integral']);
        $this->assertFalse($r['tem_parcial']);
        $this->assertEqualsWithDelta(0.0, $r['credito'], 0.001);
        $this->assertCount(1, $r['aplicacoes']);
        $this->assertSame(1, $r['aplicacoes'][0]['id']);
    }

    public function test_dois_meses_de_uma_vez(): void
    {
        $r = AlocacaoFifo::alocar(180.0, $this->parcelas([90, 90, 90]));

        $this->assertTrue($r['integral']);
        $this->assertCount(2, $r['aplicacoes']);
        $this->assertEqualsWithDelta(180.0, $r['total_aplicado'], 0.001);
    }

    public function test_valores_variaveis_por_morador(): void
    {
        // Morador paga 80; mandou 160 = dois meses.
        $r = AlocacaoFifo::alocar(160.0, $this->parcelas([80, 80, 80]));

        $this->assertTrue($r['integral']);
        $this->assertCount(2, $r['aplicacoes']);
    }

    public function test_pagamento_parcial_marca_tem_parcial(): void
    {
        $r = AlocacaoFifo::alocar(200.0, $this->parcelas([90, 90, 90]));

        $this->assertFalse($r['integral']);
        $this->assertTrue($r['tem_parcial']);
        $this->assertCount(3, $r['aplicacoes']);
        $this->assertEqualsWithDelta(20.0, $r['aplicacoes'][2]['valor'], 0.001); // sobra de 20 na 3ª
        $this->assertEqualsWithDelta(0.0, $r['credito'], 0.001);
    }

    public function test_sobra_vira_credito(): void
    {
        // Deve 270 (3x90) e pagou 300 -> 30 de troco/crédito.
        $r = AlocacaoFifo::alocar(300.0, $this->parcelas([90, 90, 90]));

        $this->assertFalse($r['integral']);
        $this->assertFalse($r['tem_parcial']);
        $this->assertEqualsWithDelta(30.0, $r['credito'], 0.001);
        $this->assertEqualsWithDelta(270.0, $r['total_aplicado'], 0.001);
    }

    public function test_sem_parcelas_tudo_vira_credito(): void
    {
        $r = AlocacaoFifo::alocar(90.0, []);

        $this->assertFalse($r['integral']);
        $this->assertEqualsWithDelta(90.0, $r['credito'], 0.001);
        $this->assertCount(0, $r['aplicacoes']);
    }
}

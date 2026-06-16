<?php

namespace Tests\Unit;

use App\Support\OfxParser;
use PHPUnit\Framework\TestCase;

class OfxParserTest extends TestCase
{
    private function ofxExemplo(): string
    {
        return <<<OFX
        OFXHEADER:100
        DATA:OFXSGML
        VERSION:102
        SECURITY:NONE
        ENCODING:USASCII
        CHARSET:1252

        <OFX>
        <BANKMSGSRSV1><STMTTRNRS><STMTRS>
        <CURDEF>BRL
        <BANKACCTFROM><BANKID>001<ACCTID>12345-6<ACCTTYPE>CHECKING</BANKACCTFROM>
        <BANKTRANLIST><DTSTART>20260601<DTEND>20260612
        <STMTTRN><TRNTYPE>CREDIT<DTPOSTED>20260603120000[-03:EST]<TRNAMT>90.00<FITID>2026060301<NAME>JOAO SILVA<MEMO>PIX RECEBIDO JOAO SILVA</STMTTRN>
        <STMTTRN><TRNTYPE>CREDIT<DTPOSTED>20260605<TRNAMT>160.00<FITID>2026060502<MEMO>PIX RECEBIDO MARIA SOUZA 123.456.789-01</STMTTRN>
        <STMTTRN><TRNTYPE>DEBIT<DTPOSTED>20260606<TRNAMT>-50.00<FITID>2026060603<MEMO>TARIFA BANCARIA</STMTTRN>
        </BANKTRANLIST>
        </STMTRS></STMTTRNRS></BANKMSGSRSV1>
        </OFX>
        OFX;
    }

    public function test_le_apenas_creditos(): void
    {
        $r = OfxParser::parse($this->ofxExemplo());

        $this->assertCount(2, $r['transacoes']); // o débito (TARIFA) é ignorado
    }

    public function test_extrai_campos_da_transacao(): void
    {
        $t = OfxParser::parse($this->ofxExemplo())['transacoes'][0];

        $this->assertEqualsWithDelta(90.0, $t['valor'], 0.001);
        $this->assertSame('2026060301', $t['fitid']);
        $this->assertSame('JOAO SILVA', $t['nome']);
        $this->assertSame('PIX', $t['tipo']);
        $this->assertSame('2026-06-03', $t['data']->format('Y-m-d'));
    }

    public function test_extrai_info_da_conta(): void
    {
        $conta = OfxParser::parse($this->ofxExemplo())['conta'];

        $this->assertSame('001', $conta['banco']);
        $this->assertSame('12345-6', $conta['conta']);
        $this->assertSame('2026-06-01', $conta['inicio']->format('Y-m-d'));
        $this->assertSame('2026-06-12', $conta['fim']->format('Y-m-d'));
    }
}

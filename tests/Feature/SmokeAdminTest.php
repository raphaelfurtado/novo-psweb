<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Smoke test de renderização: garante que as páginas autenticadas (layout + nav +
 * componentes) renderizam sem erro para um admin. Usa a conexão mysql real (somente
 * leitura) — não cria nem altera registros.
 */
class SmokeAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sobrescreve o sqlite :memory: do phpunit.xml para ler o banco real.
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3306',
            'database.connections.mysql.database' => 'websi459_psweb',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
        DB::purge('mysql');
    }

    public function test_paginas_autenticadas_renderizam_para_admin(): void
    {
        $admin = User::query()->where('role', 'admin')->first();
        $this->assertNotNull($admin, 'Nenhum admin encontrado no banco.');

        // Garante que o filtro de consentimento não interfira (sem salvar).
        $admin->consent_policy = 'S';
        $this->actingAs($admin);

        $urls = [
            '/', '/pagamentos', '/pagamentos/novo', '/pagamentos/gerar', '/pagamentos/reajuste',
            '/saidas', '/saidas/nova', '/folha', '/folha/novo',
            '/moradores', '/moradores/novo',
            '/funcionarios', '/funcionarios/novo', '/recebedores',
            '/tipos-pagamento', '/formas-pagamento', '/tipos-saida',
            '/relatorios', '/relatorios/fluxo-caixa', '/relatorios/inadimplencia',
            '/relatorios/folha-pagamento', '/relatorios/prestacao-contas',
            '/relatorios/receitas-categoria', '/relatorios/resumo-caixa',
            '/relatorios/inadimplencia-avancada', '/relatorios/extrato-morador',
            '/relatorios/demonstrativo-anual',
            '/conciliacao', '/acordos', '/acordos/novo',
            '/anexos', '/meus-pagamentos', '/senha',
        ];

        // Rotas de edição com registros reais.
        if ($morador = User::query()->whereHas('endereco')->first()) {
            $urls[] = "/moradores/{$morador->id}/editar";
        }
        if ($func = \App\Models\Funcionario::query()->first()) {
            $urls[] = "/funcionarios/{$func->id}/editar";
        }
        if ($pag = \App\Models\Pagamento::query()->first()) {
            $urls[] = "/pagamentos/{$pag->id}/editar";
        }
        if ($despesa = \App\Models\Saida::query()->whereNull('id_funcionario')->first()) {
            $urls[] = "/saidas/{$despesa->id}/editar";
        }
        if ($folha = \App\Models\Saida::query()->whereNotNull('id_funcionario')->first()) {
            $urls[] = "/folha/{$folha->id}/editar";
        }
        if ($lote = \App\Models\ConciliacaoLote::query()->first()) {
            $urls[] = "/conciliacao/{$lote->id}";
        }

        foreach ($urls as $url) {
            $response = $this->get($url);
            $this->assertSame(200, $response->status(), "Falhou ao renderizar {$url} (status {$response->status()})");
        }
    }

    public function test_geracao_de_pdf_e_excel(): void
    {
        $admin = User::query()->where('role', 'admin')->first();
        $admin->consent_policy = 'S';
        $this->actingAs($admin);

        $usuario = User::query()->whereHas('pagamentos')->first();

        $rotas = [
            '/relatorios/prestacao-contas/pdf?referencia=012025',
            '/relatorios/resumo-caixa/pdf?referencia=012025',
            '/relatorios/receitas-categoria/pdf?referencia=012025',
            "/relatorios/extrato-morador/pdf?usuario={$usuario->id}&ano=2025",
            '/relatorios/prestacao-contas/excel?referencia=012025',
            '/relatorios/receitas-categoria/excel?referencia=012025',
            '/relatorios/resumo-caixa/excel?referencia=012025',
            '/relatorios/demonstrativo-anual/excel?ano=2025',
            '/relatorios/inadimplencia-avancada/excel?ano=2025',
            // Exportação das listagens (com filtros e ordenação)
            '/pagamentos/export?fmt=pdf&ano=2025',
            '/pagamentos/export?fmt=excel&ano=2025&situacao=VENCIDO',
            '/pagamentos/export?fmt=pdf&ano=2025&ord=nome&dir=asc',
            '/pagamentos/export?fmt=excel&ano=2025&ord=valor&dir=desc',
            '/saidas/export?fmt=pdf&ano=2025',
            '/folha/export?fmt=excel&ano=2025',
        ];

        foreach ($rotas as $rota) {
            $this->get($rota)->assertOk();
        }
    }

    public function test_download_de_anexo_serve_o_arquivo(): void
    {
        $admin = User::query()->where('role', 'admin')->first();
        $admin->consent_policy = 'S';
        $this->actingAs($admin);

        // Fabrica um arquivo real no storage de uploads e um registro, exercita a
        // rota de download de ponta a ponta e desfaz tudo (arquivo + registro).
        $rel = 'teste/_smoke_' . uniqid() . '.txt';
        $abs = storage_path('app/uploads/' . $rel);
        @mkdir(dirname($abs), 0775, true);
        file_put_contents($abs, 'conteudo de teste');

        DB::beginTransaction();
        try {
            $anexo = \App\Models\Anexo::create([
                'original_name' => 'smoke.txt',
                'stored_name' => $rel,
                'mime_type' => 'text/plain',
                'size' => filesize($abs),
                'type_anex' => \App\Models\Anexo::TIPO_MORADOR,
                'id_morador' => $admin->id,
                'form' => 'ANEXO',
                'identifier' => 0,
            ]);

            $this->get("/anexos/{$anexo->id}/download")->assertOk();
        } finally {
            DB::rollBack();
            @unlink($abs);
            @rmdir(dirname($abs));
        }
    }

    public function test_morador_acessa_propria_area_e_e_bloqueado_no_admin(): void
    {
        $morador = User::query()->where('role', 'user')->first();
        $this->assertNotNull($morador, 'Nenhum morador encontrado.');

        $morador->consent_policy = 'S';
        $this->actingAs($morador);

        foreach (['/', '/meus-pagamentos', '/anexos', '/senha'] as $url) {
            $this->assertSame(200, $this->get($url)->status(), "Morador deveria acessar {$url}");
        }

        foreach (['/pagamentos', '/moradores', '/relatorios', '/funcionarios'] as $url) {
            $this->assertSame(403, $this->get($url)->status(), "Morador não deveria acessar {$url}");
        }
    }
}

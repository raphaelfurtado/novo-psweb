<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Moradores\Index as MoradoresIndex;
use App\Livewire\Pagamentos\Form as PagamentosForm;
use App\Livewire\Pagamentos\Gerar;
use App\Livewire\Pagamentos\Index as PagamentosIndex;
use App\Livewire\Pagamentos\Reajuste;
use App\Livewire\Recebedores\Index as RecebedoresIndex;
use App\Models\Endereco;
use App\Models\Pagamento;
use App\Models\Recebedor;
use App\Models\TipoPagamento;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Valida o caminho de ESCRITA contra o schema real (mapeamento de colunas, NOT NULL,
 * FKs por código). Tudo dentro de uma transação revertida — nada é persistido.
 */
class WritePathTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3306',
            'database.connections.mysql.database' => 'websi459_psweb',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
        DB::purge('mysql');
        DB::beginTransaction();

        $admin = User::query()->where('role', 'admin')->first();
        $admin->consent_policy = 'S';
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_cria_pagamento_avulso(): void
    {
        $morador = User::query()->first();

        $pag = Pagamento::create([
            'id_usuario' => $morador->id,
            'id_tipo_pagamento' => TipoPagamento::TIPO_MENSALIDADE,
            'referencia' => '012099',
            'valor' => 123.45,
            'situacao' => 'ABERTO',
            'data_insert' => now(),
        ]);

        $this->assertDatabaseHas('pagamento', ['id' => $pag->id, 'referencia' => '012099', 'situacao' => 'ABERTO']);
        $this->assertEquals('MENSALIDADE', $pag->fresh()->tipoPagamento->descricao);
    }

    public function test_login_por_telefone_e_senha(): void
    {
        $temp = User::create([
            'nome' => 'Teste Login',
            'telefone' => '91999990000',
            'aniversario' => '',
            'senha' => Hash::make('segredo123'),
            'role' => 'user',
            'consent_policy' => 'S',
        ]);

        auth()->logout();

        // Telefone formatado deve ser normalizado e autenticar via bcrypt.
        Livewire::test(Login::class)
            ->set('telefone', '(91) 99999-0000')
            ->set('senha', 'segredo123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(auth()->check());
        $this->assertSame($temp->id, auth()->id());

        // Senha errada falha.
        auth()->logout();
        Livewire::test(Login::class)
            ->set('telefone', '91999990000')
            ->set('senha', 'errada')
            ->call('login')
            ->assertHasErrors('telefone');
    }

    public function test_login_com_hash_legado_nao_quebra_e_preserva_senha(): void
    {
        // Hash "legado" custo 6, diferente do custo do app de teste (4) => needsRehash = true.
        $hashLegado = password_hash('segredo123', PASSWORD_BCRYPT, ['cost' => 6]);

        $temp = User::create([
            'nome' => 'Hash Legado',
            'telefone' => '91900000001',
            'aniversario' => '',
            'senha' => $hashLegado,
            'role' => 'user',
            'consent_policy' => 'S',
        ]);

        // Parte A: rehash desabilitado (config/hashing.php) => senha intacta, sem erro de coluna.
        auth()->logout();
        Livewire::test(Login::class)
            ->set('telefone', '91900000001')
            ->set('senha', 'segredo123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertSame($hashLegado, $temp->fresh()->senha, 'O hash legado deve permanecer intacto no login.');

        // Parte B: mesmo com rehash LIGADO, deve gravar em `senha` (e não na inexistente `password`).
        config(['hashing.rehash_on_login' => true]);
        auth()->logout();
        Auth::forgetGuards();

        Livewire::test(Login::class)
            ->set('telefone', '91900000001')
            ->set('senha', 'segredo123')
            ->call('login')
            ->assertHasNoErrors();

        $novo = $temp->fresh()->senha;
        $this->assertNotSame($hashLegado, $novo, 'Com rehash ligado, a senha é regravada na coluna senha.');
        $this->assertTrue(Hash::check('segredo123', $novo), 'A senha regravada deve continuar validando.');
    }

    public function test_filtro_vencido_so_pega_em_aberto_com_vencimento_passado(): void
    {
        $morador = User::query()->has('endereco')->first();

        $base = ['id_usuario' => $morador->id, 'id_tipo_pagamento' => TipoPagamento::TIPO_MENSALIDADE, 'data_insert' => now()];

        $vencido = Pagamento::create($base + ['situacao' => 'ABERTO', 'data_vencimento' => now()->subDays(5), 'referencia' => '012099', 'valor' => 10]);
        $futuro = Pagamento::create($base + ['situacao' => 'ABERTO', 'data_vencimento' => now()->addDays(5), 'referencia' => '022099', 'valor' => 10]);
        $pago = Pagamento::create($base + ['situacao' => 'PAGO', 'data_vencimento' => now()->subDays(5), 'referencia' => '032099', 'valor' => 10]);
        // PENDENTE com vencimento passado NÃO é vencido (status manual preservado).
        $pendente = Pagamento::create($base + ['situacao' => 'PENDENTE', 'data_vencimento' => now()->subDays(5), 'referencia' => '042099', 'valor' => 10]);

        $ids = Pagamento::vencido()->whereIn('id', [$vencido->id, $futuro->id, $pago->id, $pendente->id])->pluck('id')->all();
        $this->assertSame([$vencido->id], $ids, 'Vencido deve listar só o ABERTO com vencimento passado.');

        $this->assertTrue($vencido->fresh()->esta_vencido);
        $this->assertFalse($futuro->fresh()->esta_vencido);
        $this->assertFalse($pago->fresh()->esta_vencido);
        $this->assertFalse($pendente->fresh()->esta_vencido, 'PENDENTE não deve ser marcado como vencido.');
    }

    public function test_exclusao_de_morador_respeita_pagamentos(): void
    {
        // Sem pagamentos => confirma e exclui (com endereço em cascata).
        $u = User::create(['nome' => 'Del Me', 'telefone' => '91900000002', 'aniversario' => '',
            'senha' => Hash::make('x123456'), 'role' => 'user', 'consent_policy' => 'S']);
        Endereco::create(['id_usuario' => $u->id, 'numero' => '998', 'quadra' => 98, 'qtd_lote' => 1, 'data_insert' => now()]);

        Livewire::test(MoradoresIndex::class)->call('confirmar', $u->id)->call('excluir');
        $this->assertDatabaseMissing('users', ['id' => $u->id]);
        $this->assertDatabaseMissing('endereco', ['id_usuario' => $u->id]);

        // Com pagamento e SEM marcar a opção => bloqueado.
        $u2 = User::create(['nome' => 'Keep Me', 'telefone' => '91900000003', 'aniversario' => '',
            'senha' => Hash::make('x123456'), 'role' => 'user', 'consent_policy' => 'S']);
        Pagamento::create(['id_usuario' => $u2->id, 'id_tipo_pagamento' => TipoPagamento::TIPO_MENSALIDADE,
            'referencia' => '012099', 'valor' => 1, 'situacao' => 'ABERTO', 'data_insert' => now()]);

        Livewire::test(MoradoresIndex::class)->call('confirmar', $u2->id)->call('excluir')
            ->assertHasErrors('excluirPagamentos');
        $this->assertDatabaseHas('users', ['id' => $u2->id]);

        // Com pagamento e MARCANDO a opção => exclui morador + pagamentos.
        Livewire::test(MoradoresIndex::class)->call('confirmar', $u2->id)
            ->set('excluirPagamentos', true)->call('excluir');
        $this->assertDatabaseMissing('users', ['id' => $u2->id]);
        $this->assertSame(0, Pagamento::where('id_usuario', $u2->id)->count());
    }

    public function test_reajuste_atualiza_so_lancamentos_em_aberto(): void
    {
        $u = User::query()->has('endereco')->first();
        $base = ['id_usuario' => $u->id, 'id_tipo_pagamento' => TipoPagamento::TIPO_MENSALIDADE, 'data_insert' => now()];

        $aberto = Pagamento::create($base + ['situacao' => 'ABERTO', 'valor' => 80, 'referencia' => '012099']);
        $pago = Pagamento::create($base + ['situacao' => 'PAGO', 'valor' => 80, 'referencia' => '022099']);

        Livewire::test(Reajuste::class)
            ->set('ano', '2099')
            ->set('tipo', (string) TipoPagamento::TIPO_MENSALIDADE)
            ->set('valorAtual', '80,00')
            ->set('novoValor', '90,00')
            ->call('aplicar')
            ->assertHasNoErrors();

        $this->assertEquals(90.0, (float) $aberto->fresh()->valor, 'O lançamento em aberto deve ir para 90.');
        $this->assertEquals(80.0, (float) $pago->fresh()->valor, 'O lançamento PAGO não pode ser alterado.');
    }

    public function test_dar_baixa_marca_como_pago(): void
    {
        $u = User::query()->first();
        $p = Pagamento::create(['id_usuario' => $u->id, 'id_tipo_pagamento' => TipoPagamento::TIPO_MENSALIDADE,
            'referencia' => '012099', 'valor' => 90, 'situacao' => 'ABERTO', 'data_insert' => now()]);

        Livewire::test(PagamentosIndex::class)->call('darBaixa', $p->id);

        $this->assertSame('PAGO', $p->fresh()->situacao);
        $this->assertNotNull($p->fresh()->data_pagamento);
    }

    public function test_exclusao_de_recebedor_respeita_vinculos(): void
    {
        // Sem vínculo => exclui.
        $r = Recebedor::create(['nome' => 'RX', 'telefone' => '9100000', 'data_insert' => now()]);
        Livewire::test(RecebedoresIndex::class)->call('excluir', $r->id);
        $this->assertDatabaseMissing('recebedor', ['id' => $r->id]);

        // Vinculado a um pagamento => bloqueado.
        $r2 = Recebedor::create(['nome' => 'RY', 'telefone' => '9100001', 'data_insert' => now()]);
        $u = User::query()->first();
        Pagamento::create(['id_usuario' => $u->id, 'id_recebedor' => $r2->id,
            'id_tipo_pagamento' => TipoPagamento::TIPO_MENSALIDADE, 'referencia' => '012099',
            'valor' => 1, 'situacao' => 'ABERTO', 'data_insert' => now()]);

        Livewire::test(RecebedoresIndex::class)->call('excluir', $r2->id);
        $this->assertDatabaseHas('recebedor', ['id' => $r2->id]);
    }

    public function test_salvar_pagamento_volta_com_contexto_do_morador(): void
    {
        $u = User::query()->has('endereco')->first();
        $p = Pagamento::create(['id_usuario' => $u->id, 'id_tipo_pagamento' => TipoPagamento::TIPO_MENSALIDADE,
            'referencia' => '062099', 'valor' => 90, 'situacao' => 'ABERTO', 'data_insert' => now()]);

        // Sem filtros na URL -> fallback para ano + nome do morador.
        Livewire::test(PagamentosForm::class, ['pagamento' => $p])
            ->set('valor', '95,00')
            ->call('salvar')
            ->assertHasNoErrors()
            ->assertRedirect(route('pagamentos.index', ['ano' => '2099', 'q' => $u->nome]));
    }

    public function test_form_de_pagamento_preserva_filtros_da_url_no_voltar(): void
    {
        $p = Pagamento::query()->first();

        $resp = $this->get(route('pagamentos.edit', [
            'pagamento' => $p->id, 'q' => 'Fulano', 'ano' => '2099', 'situacao' => 'ABERTO',
        ]));

        $resp->assertOk();
        $resp->assertSee('q=Fulano', false);
        $resp->assertSee('situacao=ABERTO', false);
    }

    public function test_filtro_por_quadra_e_numero_nos_pagamentos(): void
    {
        $a = User::create(['nome' => 'Quadra77', 'telefone' => '91900000077', 'aniversario' => '',
            'senha' => Hash::make('x123456'), 'role' => 'user', 'consent_policy' => 'S']);
        Endereco::create(['id_usuario' => $a->id, 'numero' => '10', 'quadra' => 77, 'qtd_lote' => 1, 'data_insert' => now()]);

        $b = User::create(['nome' => 'Quadra88', 'telefone' => '91900000088', 'aniversario' => '',
            'senha' => Hash::make('x123456'), 'role' => 'user', 'consent_policy' => 'S']);
        Endereco::create(['id_usuario' => $b->id, 'numero' => '20', 'quadra' => 88, 'qtd_lote' => 1, 'data_insert' => now()]);

        $base = ['id_tipo_pagamento' => TipoPagamento::TIPO_MENSALIDADE, 'referencia' => '012099',
            'valor' => 10, 'situacao' => 'ABERTO', 'data_insert' => now()];
        $pa = Pagamento::create($base + ['id_usuario' => $a->id]);
        $pb = Pagamento::create($base + ['id_usuario' => $b->id]);

        // Filtro por quadra 77 -> só o pagamento do morador A.
        Livewire::test(PagamentosIndex::class)
            ->set('ano', '2099')
            ->set('quadra', '77')
            ->assertViewHas('pagamentos', fn ($p) => $p->pluck('id')->contains($pa->id)
                && ! $p->pluck('id')->contains($pb->id));

        // Filtro por número 20 -> só o pagamento do morador B.
        Livewire::test(PagamentosIndex::class)
            ->set('ano', '2099')
            ->set('numero', '20')
            ->assertViewHas('pagamentos', fn ($p) => $p->pluck('id')->contains($pb->id)
                && ! $p->pluck('id')->contains($pa->id));
    }

    public function test_gerar_apenas_competencias_selecionadas(): void
    {
        $totalMoradores = User::has('endereco')->count();

        Livewire::test(Gerar::class)
            ->set('ano', '2098')
            ->set('valor', '80,00')
            ->set('dia_vencimento', 10)
            ->set('mesesSelecionados', ['06', '07', '08', '09', '10', '11', '12']) // jun-dez
            ->call('gerar')
            ->assertHasNoErrors();

        $base = Pagamento::whereRaw("SUBSTR(referencia, 3, 4) = '2098'")
            ->where('id_tipo_pagamento', TipoPagamento::TIPO_MENSALIDADE);

        $this->assertSame($totalMoradores * 7, (clone $base)->count(), 'Devem existir 7 competências por morador.');
        $this->assertSame(0, Pagamento::where('referencia', '012098')
            ->where('id_tipo_pagamento', TipoPagamento::TIPO_MENSALIDADE)->count(), 'Janeiro não deveria ser gerado.');
        $this->assertSame($totalMoradores, Pagamento::where('referencia', '062098')
            ->where('id_tipo_pagamento', TipoPagamento::TIPO_MENSALIDADE)->count(), 'Junho deveria ser gerado.');
    }

    public function test_ordenacao_alfabetica_por_morador(): void
    {
        $z = User::create(['nome' => 'ZZZ Teste', 'telefone' => '91900000097', 'aniversario' => '',
            'senha' => Hash::make('x123456'), 'role' => 'user', 'consent_policy' => 'S']);
        Endereco::create(['id_usuario' => $z->id, 'numero' => '10', 'quadra' => 1, 'qtd_lote' => 1, 'data_insert' => now()]);

        $a = User::create(['nome' => 'AAA Teste', 'telefone' => '91900000098', 'aniversario' => '',
            'senha' => Hash::make('x123456'), 'role' => 'user', 'consent_policy' => 'S']);
        Endereco::create(['id_usuario' => $a->id, 'numero' => '11', 'quadra' => 1, 'qtd_lote' => 1, 'data_insert' => now()]);

        $base = ['id_tipo_pagamento' => TipoPagamento::TIPO_MENSALIDADE, 'referencia' => '012097',
            'valor' => 10, 'situacao' => 'ABERTO', 'data_insert' => now()];
        $pz = Pagamento::create($base + ['id_usuario' => $z->id]);
        $pa = Pagamento::create($base + ['id_usuario' => $a->id]);

        // A-Z: AAA primeiro.
        Livewire::test(PagamentosIndex::class)
            ->set('ano', '2097')->set('sortField', 'nome')->set('sortDirection', 'asc')
            ->assertViewHas('pagamentos', fn ($p) => $p->first()?->id === $pa->id && $p->last()?->id === $pz->id);

        // Z-A: ZZZ primeiro.
        Livewire::test(PagamentosIndex::class)
            ->set('ano', '2097')->set('sortField', 'nome')->set('sortDirection', 'desc')
            ->assertViewHas('pagamentos', fn ($p) => $p->first()?->id === $pz->id);
    }

    public function test_gerar_mensalidades_do_ano_via_livewire(): void
    {
        Livewire::test(Gerar::class)
            ->set('ano', '2099')
            ->set('valor', '80,00')
            ->set('dia_vencimento', 10)
            ->call('gerar')
            ->assertHasNoErrors();

        $totalMoradores = User::has('endereco')->count();

        $this->assertSame(
            $totalMoradores * 12,
            Pagamento::whereRaw("SUBSTR(referencia, 3, 4) = '2099'")
                ->where('id_tipo_pagamento', TipoPagamento::TIPO_MENSALIDADE)
                ->count()
        );
    }
}

<?php

use App\Http\Controllers\AnexoController;
use App\Http\Controllers\MovimentacaoExportController;
use App\Http\Controllers\RelatorioPdfController;
use App\Livewire;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas públicas (login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', Livewire\Auth\Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Página de consentimento (LGPD) — exige login, mas não o filtro de consent
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/consent', Livewire\Auth\Consent::class)->name('consent');
});

/*
|--------------------------------------------------------------------------
| Rotas autenticadas (com consentimento aceito)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'consent'])->group(function () {
    Route::get('/', Livewire\Dashboard\Index::class)->name('dashboard');

    // Área do morador
    Route::get('/meus-pagamentos', Livewire\Morador\MeusPagamentos::class)->name('meus-pagamentos');
    Route::get('/senha', Livewire\Conta\TrocarSenha::class)->name('senha.edit');

    // Anexos (admin vê todos; morador vê os seus)
    Route::get('/anexos', Livewire\Anexos\Index::class)->name('anexos.index');
    Route::get('/anexos/{anexo}/download', [AnexoController::class, 'download'])->name('anexos.download');

    /*
    |----------------------------------------------------------------------
    | Área administrativa
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        // Movimentações
        Route::get('/pagamentos', Livewire\Pagamentos\Index::class)->name('pagamentos.index');
        Route::get('/pagamentos/export', [MovimentacaoExportController::class, 'pagamentos'])->name('pagamentos.export');
        Route::get('/pagamentos/novo', Livewire\Pagamentos\Form::class)->name('pagamentos.create');
        Route::get('/pagamentos/{pagamento}/editar', Livewire\Pagamentos\Form::class)->name('pagamentos.edit');
        Route::get('/pagamentos/gerar', Livewire\Pagamentos\Gerar::class)->name('pagamentos.gerar');
        Route::get('/pagamentos/reajuste', Livewire\Pagamentos\Reajuste::class)->name('pagamentos.reajuste');

        Route::get('/saidas', Livewire\Saidas\Index::class)->name('saidas.index');
        Route::get('/saidas/export', [MovimentacaoExportController::class, 'saidas'])->name('saidas.export');
        Route::get('/saidas/nova', Livewire\Saidas\Form::class)->name('saidas.create');
        Route::get('/saidas/{saida}/editar', Livewire\Saidas\Form::class)->name('saidas.edit');

        Route::get('/folha', Livewire\Folha\Index::class)->name('folha.index');
        Route::get('/folha/export', [MovimentacaoExportController::class, 'folha'])->name('folha.export');
        Route::get('/folha/novo', Livewire\Folha\Form::class)->name('folha.create');
        Route::get('/folha/{saida}/editar', Livewire\Folha\Form::class)->name('folha.edit');

        // Conciliação bancária (OFX) e acordos
        Route::get('/conciliacao', Livewire\Conciliacao\Index::class)->name('conciliacao.index');
        Route::get('/conciliacao/{lote}', Livewire\Conciliacao\Revisar::class)->name('conciliacao.revisar');
        Route::get('/acordos', Livewire\Acordos\Index::class)->name('acordos.index');
        Route::get('/acordos/novo', Livewire\Acordos\Form::class)->name('acordos.create');

        // Cadastros
        Route::get('/moradores', Livewire\Moradores\Index::class)->name('moradores.index');
        Route::get('/moradores/novo', Livewire\Moradores\Form::class)->name('moradores.create');
        Route::get('/moradores/{user}/editar', Livewire\Moradores\Form::class)->name('moradores.edit');

        Route::get('/funcionarios', Livewire\Funcionarios\Index::class)->name('funcionarios.index');
        Route::get('/funcionarios/novo', Livewire\Funcionarios\Form::class)->name('funcionarios.create');
        Route::get('/funcionarios/{funcionario}/editar', Livewire\Funcionarios\Form::class)->name('funcionarios.edit');

        Route::get('/recebedores', Livewire\Recebedores\Index::class)->name('recebedores.index');
        Route::get('/tipos-pagamento', Livewire\TiposPagamento\Index::class)->name('tipos-pagamento.index');
        Route::get('/formas-pagamento', Livewire\FormasPagamento\Index::class)->name('formas-pagamento.index');
        Route::get('/tipos-saida', Livewire\TiposSaida\Index::class)->name('tipos-saida.index');

        // Relatórios
        Route::prefix('relatorios')->name('relatorios.')->group(function () {
            Route::get('/', Livewire\Relatorios\Index::class)->name('index');
            Route::get('/fluxo-caixa', Livewire\Relatorios\FluxoCaixa::class)->name('fluxo-caixa');
            Route::get('/inadimplencia', Livewire\Relatorios\Inadimplencia::class)->name('inadimplencia');
            Route::get('/inadimplencia-avancada', Livewire\Relatorios\InadimplenciaAvancada::class)->name('inadimplencia-avancada');
            Route::get('/folha-pagamento', Livewire\Relatorios\FolhaPagamento::class)->name('folha-pagamento');
            Route::get('/prestacao-contas', Livewire\Relatorios\PrestacaoContas::class)->name('prestacao-contas');
            Route::get('/receitas-categoria', Livewire\Relatorios\ReceitasCategoria::class)->name('receitas-categoria');
            Route::get('/resumo-caixa', Livewire\Relatorios\ResumoCaixa::class)->name('resumo-caixa');
            Route::get('/extrato-morador', Livewire\Relatorios\ExtratoMorador::class)->name('extrato-morador');
            Route::get('/demonstrativo-anual', Livewire\Relatorios\DemonstrativoAnual::class)->name('demonstrativo-anual');

            // PDFs e exportações (respostas diretas — controller)
            Route::get('/{relatorio}/pdf', [RelatorioPdfController::class, 'pdf'])->name('pdf');
            Route::get('/{relatorio}/excel', [RelatorioPdfController::class, 'excel'])->name('excel');
        });
    });
});

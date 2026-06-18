<?php

namespace App\Livewire\Pagamentos;

use App\Models\Pagamento;
use App\Models\TipoPagamento;
use App\Models\User;
use App\Support\Referencia;
use App\Support\Valor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Geração em lote das mensalidades de um ano (tipo 3 = MENSALIDADE, situação ABERTO).
 * Reimplementa em PHP a antiga procedure gerar_pagamentos_ano_flexivel (inexistente no banco),
 * de forma transparente: pula meses que já possuem mensalidade para o morador.
 */
class Gerar extends Component
{
    public string $ano;
    public string $valor = '';
    public int $dia_vencimento = 10;

    /** Competências (meses '01'..'12') que serão geradas. */
    public array $mesesSelecionados = [];

    public ?array $resultado = null;

    public function mount(): void
    {
        $this->ano = Carbon::now()->format('Y');
        $this->mesesSelecionados = array_keys(Referencia::MESES); // todos por padrão
    }

    protected function rules(): array
    {
        return [
            'ano' => ['required', 'digits:4'],
            'valor' => ['required'],
            'dia_vencimento' => ['required', 'integer', 'between:1,28'],
            'mesesSelecionados' => ['required', 'array', 'min:1'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'dia_vencimento.between' => 'O dia deve estar entre 1 e 28.',
            'mesesSelecionados.required' => 'Selecione ao menos uma competência.',
            'mesesSelecionados.min' => 'Selecione ao menos uma competência.',
        ];
    }

    public function marcarTodos(): void
    {
        $this->mesesSelecionados = array_keys(Referencia::MESES);
    }

    public function limparMeses(): void
    {
        $this->mesesSelecionados = [];
    }

    public function gerar(): void
    {
        $this->validate();

        $valor = Valor::paraDecimal($this->valor);
        $moradores = User::has('endereco')->get();

        $criados = 0;
        $pulados = 0;

        // Garante ordem cronológica e remove duplicatas.
        $meses = collect($this->mesesSelecionados)->map(fn ($m) => (int) $m)->unique()->sort()->values();

        DB::transaction(function () use ($moradores, $valor, $meses, &$criados, &$pulados) {
            foreach ($moradores as $morador) {
                foreach ($meses as $m) {
                    $referencia = sprintf('%02d%s', $m, $this->ano);

                    $existe = Pagamento::where('id_usuario', $morador->id)
                        ->where('referencia', $referencia)
                        ->where('id_tipo_pagamento', TipoPagamento::TIPO_MENSALIDADE)
                        ->exists();

                    if ($existe) {
                        $pulados++;

                        continue;
                    }

                    Pagamento::create([
                        'id_usuario' => $morador->id,
                        'id_tipo_pagamento' => TipoPagamento::TIPO_MENSALIDADE,
                        'data_vencimento' => Carbon::create((int) $this->ano, $m, $this->dia_vencimento),
                        'referencia' => $referencia,
                        'valor' => $valor,
                        'situacao' => 'ABERTO',
                        'data_insert' => now(),
                    ]);
                    $criados++;
                }
            }
        });

        $this->resultado = [
            'criados' => $criados,
            'pulados' => $pulados,
            'moradores' => $moradores->count(),
        ];

        session()->flash('success', "Geração concluída: {$criados} mensalidade(s) criada(s), {$pulados} já existente(s).");
    }

    public function render()
    {
        $ano = $this->ano;

        return view('livewire.pagamentos.gerar', [
            'meses' => Referencia::MESES,
            'totalMoradores' => User::has('endereco')->count(),
            'semPagamento' => User::has('endereco')
                ->whereDoesntHave('pagamentos', fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$ano])
                    ->where('id_tipo_pagamento', TipoPagamento::TIPO_MENSALIDADE))
                ->count(),
        ]);
    }
}

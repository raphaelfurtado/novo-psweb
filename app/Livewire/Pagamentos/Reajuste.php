<?php

namespace App\Livewire\Pagamentos;

use App\Models\Pagamento;
use App\Models\TipoPagamento;
use App\Models\User;
use App\Support\Referencia;
use App\Support\Valor;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Atualização de valor em massa dos lançamentos EM ABERTO (ABERTO/PENDENTE).
 * Resolve o reajuste anual: ex. mudar todas as mensalidades abertas de 2026 de R$80 para R$90,
 * sem mexer nos pagamentos já quitados. Mostra prévia antes de aplicar.
 */
class Reajuste extends Component
{
    public string $ano;
    public string $mes = '';
    public string $tipo = '';
    public ?int $usuario = null;
    public string $valorAtual = '';
    public string $novoValor = '';

    public ?array $resultado = null;

    public function mount(): void
    {
        $this->ano = Carbon::now()->format('Y');
        $this->tipo = (string) TipoPagamento::TIPO_MENSALIDADE; // padrão: mensalidade
    }

    protected function rules(): array
    {
        return [
            'ano' => ['required', 'digits:4'],
            'novoValor' => ['required'],
        ];
    }

    protected function messages(): array
    {
        return ['required' => 'Campo obrigatório.'];
    }

    /** Query dos lançamentos em aberto que casam com os filtros. */
    protected function alvo()
    {
        return Pagamento::query()
            ->whereIn('situacao', ['ABERTO', 'PENDENTE'])
            ->when($this->ano, fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$this->ano]))
            ->when($this->mes, fn ($q) => $q->whereRaw('SUBSTR(referencia, 1, 2) = ?', [$this->mes]))
            ->when($this->tipo !== '', fn ($q) => $q->where('id_tipo_pagamento', $this->tipo))
            ->when($this->usuario, fn ($q) => $q->where('id_usuario', $this->usuario))
            ->when($this->valorAtual !== '' && Valor::paraDecimal($this->valorAtual) !== null,
                fn ($q) => $q->where('valor', Valor::paraDecimal($this->valorAtual)));
    }

    public function aplicar(): void
    {
        $this->validate();

        $novo = Valor::paraDecimal($this->novoValor);

        if ($novo === null || $novo <= 0) {
            $this->addError('novoValor', 'Informe um valor válido maior que zero.');

            return;
        }

        $afetados = $this->alvo()->update(['valor' => $novo]);

        $this->resultado = [
            'afetados' => $afetados,
            'novoValor' => $novo,
        ];

        session()->flash('success', "{$afetados} lançamento(s) em aberto atualizado(s) para " . Valor::moeda($novo) . '.');
    }

    public function render()
    {
        $alvo = $this->alvo();
        $qtd = (clone $alvo)->count();
        $totalAtual = (float) (clone $alvo)->sum('valor');
        $novo = Valor::paraDecimal($this->novoValor);
        $totalNovo = $novo !== null ? $qtd * $novo : null;

        return view('livewire.pagamentos.reajuste', [
            'meses' => Referencia::MESES,
            'tipos' => TipoPagamento::orderBy('codigo')->get(),
            'moradores' => User::orderBy('nome')->get(['id', 'nome']),
            'qtd' => $qtd,
            'totalAtual' => $totalAtual,
            'totalNovo' => $totalNovo,
        ]);
    }
}

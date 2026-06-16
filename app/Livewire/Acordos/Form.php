<?php

namespace App\Livewire\Acordos;

use App\Models\User;
use App\Services\AcordoService;
use App\Services\ConciliacaoService;
use App\Support\Valor;
use Livewire\Component;

/**
 * Criação de acordo: escolhe o morador, marca as parcelas atrasadas a incluir,
 * informa o valor acordado (com desconto opcional) e em quantas vezes.
 */
class Form extends Component
{
    public ?int $moradorId = null;
    public array $parcelasSel = [];   // [id_pagamento => bool]
    public string $valorAcordado = '';
    public int $numParcelas = 1;
    public string $observacao = '';

    public function updatedMoradorId(): void
    {
        $this->parcelasSel = [];
        $this->valorAcordado = '';
    }

    /** Marca/desmarca todas as parcelas em aberto do morador. */
    public function selecionarTodas(): void
    {
        foreach ($this->parcelas() as $p) {
            $this->parcelasSel[$p->id] = true;
        }
    }

    private function parcelas()
    {
        if (! $this->moradorId) {
            return collect();
        }

        $morador = User::find($this->moradorId);

        return $morador ? app(ConciliacaoService::class)->parcelasFifo($morador) : collect();
    }

    public function salvar()
    {
        $parcelas = $this->parcelas();
        $ids = collect($this->parcelasSel)->filter()->keys()
            ->filter(fn ($id) => $parcelas->contains('id', (int) $id))
            ->map(fn ($id) => (int) $id)
            ->all();

        $valor = Valor::paraDecimal($this->valorAcordado);

        $this->validate([
            'moradorId' => ['required'],
            'numParcelas' => ['required', 'integer', 'min:1', 'max:60'],
        ], [], ['moradorId' => 'morador', 'numParcelas' => 'número de parcelas']);

        if (empty($ids)) {
            session()->flash('error', 'Selecione ao menos uma parcela atrasada.');

            return;
        }
        if (! $valor || $valor <= 0) {
            session()->flash('error', 'Informe o valor acordado.');

            return;
        }

        $acordo = app(AcordoService::class)->criar(
            User::find($this->moradorId),
            $ids,
            $valor,
            $this->numParcelas,
            $this->observacao ?: null,
            auth()->id(),
        );

        session()->flash('success', "Acordo criado: {$acordo->parcelas()->count()} parcela(s), "
            . Valor::moeda($acordo->valor_acordado) . ' (desconto ' . Valor::moeda($acordo->desconto) . ').');

        return $this->redirectRoute('acordos.index', navigate: true);
    }

    public function render()
    {
        $parcelas = $this->parcelas();

        $original = round(
            $parcelas->filter(fn ($p) => ! empty($this->parcelasSel[$p->id]))->sum('saldo_aberto'),
            2
        );
        $valor = Valor::paraDecimal($this->valorAcordado) ?? 0;
        $desconto = max(0, round($original - $valor, 2));

        return view('livewire.acordos.form', [
            'moradores' => User::orderBy('nome')->get(['id', 'nome']),
            'parcelas' => $parcelas,
            'original' => $original,
            'desconto' => $desconto,
        ]);
    }
}

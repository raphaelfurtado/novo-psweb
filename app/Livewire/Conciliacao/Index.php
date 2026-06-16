<?php

namespace App\Livewire\Conciliacao;

use App\Models\ConciliacaoLote;
use App\Models\Recebimento;
use App\Services\ConciliacaoService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Conciliação por extrato: importa um arquivo OFX e lista os lotes já importados.
 */
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $arquivo = null;

    public function importar()
    {
        $this->validate(
            ['arquivo' => ['required', 'file', 'max:8192']],
            ['arquivo.required' => 'Selecione o arquivo .ofx do extrato.', 'arquivo.max' => 'Arquivo muito grande (máx. 8MB).']
        );

        $conteudo = $this->arquivo->get();
        $lote = app(ConciliacaoService::class)->importar(
            $conteudo,
            $this->arquivo->getClientOriginalName(),
            auth()->id(),
        );

        $this->reset('arquivo');

        if ($lote->qtd_transacoes === 0) {
            session()->flash('error', 'Nenhum crédito novo encontrado no arquivo (já importado ou só débitos).');

            return;
        }

        session()->flash('success', "Extrato importado: {$lote->qtd_transacoes} crédito(s) lido(s).");

        return $this->redirectRoute('conciliacao.revisar', $lote, navigate: true);
    }

    public function render()
    {
        $lotes = ConciliacaoLote::query()
            ->withCount([
                'recebimentos',
                'recebimentos as pendentes_count' => fn ($q) => $q->where('status', Recebimento::STATUS_PENDENTE),
                'recebimentos as conciliados_count' => fn ($q) => $q->where('status', Recebimento::STATUS_CONCILIADO),
            ])
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.conciliacao.index', ['lotes' => $lotes]);
    }
}

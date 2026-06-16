<?php

namespace App\Livewire\Relatorios;

use App\Models\Pagamento;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

class ExtratoMorador extends Component
{
    #[Url]
    public ?int $usuario = null;

    #[Url]
    public string $ano = '';

    public function mount(): void
    {
        $this->ano = $this->ano ?: Carbon::now()->format('Y');
    }

    public function render()
    {
        $pagamentos = collect();
        $morador = $this->usuario ? User::with('endereco')->find($this->usuario) : null;

        if ($morador) {
            $pagamentos = Pagamento::query()
                ->where('id_usuario', $morador->id)
                ->when($this->ano, fn ($q) => $q->whereRaw('SUBSTR(referencia, 3, 4) = ?', [$this->ano]))
                ->with(['tipoPagamento', 'formaPagamento'])
                ->orderByDesc('referencia')->orderByDesc('id')->get();
        }

        return view('livewire.relatorios.extrato-morador', [
            'moradores' => User::orderBy('nome')->get(['id', 'nome']),
            'anos' => $this->anosDisponiveis(),
            'morador' => $morador,
            'pagamentos' => $pagamentos,
            'totalPago' => (float) $pagamentos->where('situacao', 'PAGO')->sum('valor'),
            'totalAberto' => (float) $pagamentos->whereIn('situacao', ['ABERTO', 'PENDENTE'])->sum('valor'),
        ]);
    }

    protected function anosDisponiveis(): array
    {
        $anos = Pagamento::query()->selectRaw('DISTINCT SUBSTR(referencia, 3, 4) as ano')
            ->orderByDesc('ano')->pluck('ano')->filter()->values()->all();
        $atual = Carbon::now()->format('Y');
        if (! in_array($atual, $anos)) {
            array_unshift($anos, $atual);
        }

        return $anos;
    }
}

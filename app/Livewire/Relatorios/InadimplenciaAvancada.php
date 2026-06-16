<?php

namespace App\Livewire\Relatorios;

use App\Models\Pagamento;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class InadimplenciaAvancada extends Component
{
    #[Url]
    public string $ano = '';

    #[Url]
    public string $quadra = '';

    public function render()
    {
        $ranking = Pagamento::query()
            ->join('users', 'users.id', '=', 'pagamento.id_usuario')
            ->leftJoin('endereco', 'endereco.id_usuario', '=', 'users.id')
            ->whereIn('pagamento.situacao', ['ABERTO', 'PENDENTE'])
            ->when($this->ano, fn ($q) => $q->whereRaw('SUBSTR(pagamento.referencia, 3, 4) = ?', [$this->ano]))
            ->when($this->quadra !== '', fn ($q) => $q->where('endereco.quadra', $this->quadra))
            ->selectRaw('users.id, users.nome, users.telefone, endereco.quadra, endereco.numero,
                SUM(pagamento.valor) as total_aberto,
                COUNT(DISTINCT pagamento.referencia) as meses_atraso,
                COUNT(*) as qtd')
            ->groupBy('users.id', 'users.nome', 'users.telefone', 'endereco.quadra', 'endereco.numero')
            ->orderByDesc('total_aberto')
            ->get();

        return view('livewire.relatorios.inadimplencia-avancada', [
            'ranking' => $ranking,
            'anos' => $this->anosDisponiveis(),
            'quadras' => DB::table('endereco')->select('quadra')->distinct()->orderBy('quadra')->pluck('quadra'),
            'total' => (float) $ranking->sum('total_aberto'),
        ]);
    }

    protected function anosDisponiveis(): array
    {
        return Pagamento::query()->selectRaw('DISTINCT SUBSTR(referencia, 3, 4) as ano')
            ->orderByDesc('ano')->pluck('ano')->filter()->values()->all();
    }
}

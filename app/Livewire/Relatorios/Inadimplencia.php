<?php

namespace App\Livewire\Relatorios;

use App\Livewire\Concerns\ComPeriodo;
use App\Models\Pagamento;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class Inadimplencia extends Component
{
    use ComPeriodo;

    #[Url]
    public string $quadra = '';

    public function render()
    {
        $r = $this->periodo();

        $inadimplentes = Pagamento::query()
            ->join('users', 'users.id', '=', 'pagamento.id_usuario')
            ->join('endereco', 'endereco.id_usuario', '=', 'users.id')
            ->whereIn('pagamento.situacao', ['ABERTO', 'PENDENTE'])
            ->noPeriodo($r['inicio'], $r['fim'], 'pagamento.referencia')
            ->when($this->quadra !== '', fn ($q) => $q->where('endereco.quadra', $this->quadra))
            ->selectRaw('users.nome, users.telefone, endereco.quadra, endereco.numero,
                pagamento.valor, pagamento.data_vencimento, pagamento.situacao, pagamento.referencia')
            ->orderBy('endereco.quadra')->orderBy('endereco.numero')
            ->get();

        $quadras = DB::table('endereco')->select('quadra')->distinct()->orderBy('quadra')->pluck('quadra');

        return view('livewire.relatorios.inadimplencia', [
            'r' => $r,
            'meses' => $this->mesesDisponiveis(),
            'inadimplentes' => $inadimplentes,
            'total' => (float) $inadimplentes->sum('valor'),
            'quadras' => $quadras,
        ]);
    }
}

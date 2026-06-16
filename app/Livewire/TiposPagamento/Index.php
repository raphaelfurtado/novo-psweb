<?php

namespace App\Livewire\TiposPagamento;

use App\Livewire\Cadastros\CodigoDescricaoCrud;
use App\Models\TipoPagamento;

class Index extends CodigoDescricaoCrud
{
    protected function modelClass(): string
    {
        return TipoPagamento::class;
    }

    public function titulo(): string
    {
        return 'Tipos de Pagamento';
    }

    public function singular(): string
    {
        return 'Tipo de pagamento';
    }
}

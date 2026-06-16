<?php

namespace App\Livewire\FormasPagamento;

use App\Livewire\Cadastros\CodigoDescricaoCrud;
use App\Models\FormaPagamento;

class Index extends CodigoDescricaoCrud
{
    protected function modelClass(): string
    {
        return FormaPagamento::class;
    }

    public function titulo(): string
    {
        return 'Formas de Pagamento';
    }

    public function singular(): string
    {
        return 'Forma de pagamento';
    }
}

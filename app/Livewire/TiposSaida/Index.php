<?php

namespace App\Livewire\TiposSaida;

use App\Livewire\Cadastros\CodigoDescricaoCrud;
use App\Models\TipoSaida;

class Index extends CodigoDescricaoCrud
{
    protected function modelClass(): string
    {
        return TipoSaida::class;
    }

    public function titulo(): string
    {
        return 'Tipos de Saída';
    }

    public function singular(): string
    {
        return 'Tipo de saída';
    }
}

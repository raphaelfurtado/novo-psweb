<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Forma de pagamento (referenciada por `codigo`): 1=Cartão Crédito, 2=Débito, 3=PIX, 4=Boleto, 5=Dinheiro. */
class FormaPagamento extends Model
{
    protected $table = 'forma_pagamento';

    public $timestamps = false;

    protected $fillable = ['codigo', 'descricao', 'registro_ativo', 'data_insert'];

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('registro_ativo', 1);
    }
}

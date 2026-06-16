<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de pagamento (categoria). É referenciado por `codigo` em pagamento/saida.
 * Códigos de domínio: 1=13º PORTARIA, 2=CONTRIBUIÇÃO, 3=MENSALIDADE,
 * 4=CONCRETO, 5=ROÇAGEM, 6=OUTROS, 7=PODAGEM.
 */
class TipoPagamento extends Model
{
    protected $table = 'tipo_pagamento';

    public $timestamps = false;

    protected $fillable = ['codigo', 'descricao', 'registro_ativo', 'data_insert'];

    /** Tipos que entram no caixa (entradas reais). */
    public const TIPOS_ENTRADA = [3, 4, 5];

    /** Tipo "Concreto" — caixa segregado. */
    public const TIPO_CONCRETO = 4;

    /** Tipo "Mensalidade" — recorrente mensal. */
    public const TIPO_MENSALIDADE = 3;

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('registro_ativo', 1);
    }
}

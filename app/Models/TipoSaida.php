<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Tipo de saída (folha): 1=Salário Integral, 2=Antecipação, 3=Última parte, 4=Outros. Referenciado por `codigo`. */
class TipoSaida extends Model
{
    protected $table = 'tipo_saida';

    public $timestamps = false;

    protected $fillable = ['codigo', 'descricao', 'registro_ativo', 'data_insert'];

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('registro_ativo', 1);
    }
}

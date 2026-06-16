<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recebedor extends Model
{
    protected $table = 'recebedor';

    public $timestamps = false;

    protected $fillable = [
        'nome', 'telefone', 'telefone_2', 'data_nascimento', 'data_insert',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
    ];
}

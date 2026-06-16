<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Endereco extends Model
{
    protected $table = 'endereco';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario', 'rua', 'numero', 'quadra', 'qtd_lote', 'data_insert',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}

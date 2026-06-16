<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lote de conciliação: uma importação de arquivo OFX (extrato bancário).
 * Agrupa os recebimentos (créditos) lidos do arquivo.
 */
class ConciliacaoLote extends Model
{
    protected $table = 'conciliacao_lote';

    protected $fillable = [
        'arquivo', 'banco', 'conta', 'periodo_inicio', 'periodo_fim',
        'qtd_transacoes', 'total_creditos', 'id_importador',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fim' => 'date',
        'total_creditos' => 'decimal:2',
    ];

    public function recebimentos(): HasMany
    {
        return $this->hasMany(Recebimento::class, 'id_lote');
    }

    public function importador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_importador');
    }

    /** Quantos recebimentos do lote ainda aguardam decisão. */
    public function getPendentesAttribute(): int
    {
        return $this->recebimentos->where('status', Recebimento::STATUS_PENDENTE)->count();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Recebimento: uma linha de crédito (PIX/TED) lida do extrato OFX.
 *
 * Fluxo: PENDENTE (aguardando decisão) -> CONCILIADO (baixa feita) | IGNORADO.
 * O `fitid` é o identificador único da transação no OFX e evita baixa em
 * duplicidade quando o mesmo extrato é reimportado.
 */
class Recebimento extends Model
{
    protected $table = 'recebimento';

    protected $fillable = [
        'id_lote', 'fitid', 'data', 'valor', 'nome_pagador', 'cpf_pagador',
        'memo', 'tipo', 'id_usuario', 'id_acordo', 'status', 'valor_credito',
        'observacao', 'conciliado_em', 'id_conciliador',
    ];

    protected $casts = [
        'data' => 'date',
        'valor' => 'decimal:2',
        'valor_credito' => 'decimal:2',
        'conciliado_em' => 'datetime',
    ];

    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_CONCILIADO = 'CONCILIADO';
    public const STATUS_IGNORADO = 'IGNORADO';

    public function lote(): BelongsTo
    {
        return $this->belongsTo(ConciliacaoLote::class, 'id_lote');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function acordo(): BelongsTo
    {
        return $this->belongsTo(Acordo::class, 'id_acordo');
    }

    public function conciliador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_conciliador');
    }

    /** Parcelas (pagamento) quitadas por este recebimento, com o valor aplicado em cada. */
    public function parcelas(): BelongsToMany
    {
        return $this->belongsToMany(Pagamento::class, 'recebimento_parcela', 'id_recebimento', 'id_pagamento')
            ->withPivot('valor_aplicado')
            ->withTimestamps();
    }

    public function scopePendente(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }

    public function isPendente(): bool
    {
        return $this->status === self::STATUS_PENDENTE;
    }
}

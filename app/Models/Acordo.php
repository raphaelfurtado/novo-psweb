<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Acordo: negociação de um bloco de parcelas atrasadas de um morador.
 *
 * Pode ter desconto (valor_acordado < valor_original) e ser pago à vista ou
 * parcelado. Enquanto ABERTO, as parcelas vinculadas ficam "reservadas" e são
 * ignoradas pela conciliação automática (FIFO). Ao QUITAR, as parcelas viram PAGO.
 */
class Acordo extends Model
{
    protected $table = 'acordo';

    protected $fillable = [
        'id_usuario', 'valor_original', 'valor_acordado', 'desconto',
        'num_parcelas', 'valor_pago', 'situacao', 'data_acordo',
        'observacao', 'id_criador', 'quitado_em',
    ];

    protected $casts = [
        'valor_original' => 'decimal:2',
        'valor_acordado' => 'decimal:2',
        'desconto' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'data_acordo' => 'date',
        'quitado_em' => 'datetime',
    ];

    public const SITUACAO_ABERTO = 'ABERTO';
    public const SITUACAO_QUITADO = 'QUITADO';
    public const SITUACAO_CANCELADO = 'CANCELADO';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_criador');
    }

    /** Parcelas (pagamento) cobertas pelo acordo. */
    public function parcelas(): BelongsToMany
    {
        return $this->belongsToMany(Pagamento::class, 'acordo_parcela', 'id_acordo', 'id_pagamento')
            ->withTimestamps();
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(Recebimento::class, 'id_acordo');
    }

    public function scopeAberto(Builder $query): Builder
    {
        return $query->where('situacao', self::SITUACAO_ABERTO);
    }

    public function isAberto(): bool
    {
        return $this->situacao === self::SITUACAO_ABERTO;
    }

    /** Quanto ainda falta receber para quitar o acordo. */
    public function getSaldoAttribute(): float
    {
        return max(0, (float) $this->valor_acordado - (float) $this->valor_pago);
    }
}

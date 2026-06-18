<?php

namespace App\Models;

use App\Models\Concerns\FiltraPeriodo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lançamento de receita / mensalidade de um morador.
 *
 * Quirk de schema: id_tipo_pagamento e id_forma_pagamento referenciam o `codigo`
 * (não o id) de tipo_pagamento/forma_pagamento.
 */
class Pagamento extends Model
{
    use FiltraPeriodo;

    protected $table = 'pagamento';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario', 'id_recebedor', 'id_tipo_pagamento', 'id_forma_pagamento',
        'data_pagamento', 'data_vencimento', 'referencia', 'valor', 'situacao',
        'observacao', 'data_insert',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pagamento' => 'datetime',
        'data_vencimento' => 'datetime',
        'data_insert' => 'datetime',
    ];

    public const SITUACOES = ['ABERTO', 'PAGO', 'CANCELADO', 'PENDENTE', 'ISENTO'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function recebedor(): BelongsTo
    {
        return $this->belongsTo(Recebedor::class, 'id_recebedor');
    }

    public function tipoPagamento(): BelongsTo
    {
        return $this->belongsTo(TipoPagamento::class, 'id_tipo_pagamento', 'codigo');
    }

    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class, 'id_forma_pagamento', 'codigo');
    }

    public function comprovantes(): HasMany
    {
        return $this->hasMany(Anexo::class, 'identifier')->where('form', 'PAGAMENTO');
    }

    /** Recebimentos (extrato OFX) que baixaram esta parcela, com o valor aplicado. */
    public function recebimentos(): BelongsToMany
    {
        return $this->belongsToMany(Recebimento::class, 'recebimento_parcela', 'id_pagamento', 'id_recebimento')
            ->withPivot('valor_aplicado')
            ->withTimestamps();
    }

    /** Acordos que incluem esta parcela. */
    public function acordos(): BelongsToMany
    {
        return $this->belongsToMany(Acordo::class, 'acordo_parcela', 'id_pagamento', 'id_acordo')
            ->withTimestamps();
    }

    /** Total já recebido (somatório das aplicações de recebimentos) nesta parcela. */
    public function getValorPagoAttribute(): float
    {
        return (float) $this->recebimentos->sum(fn ($r) => $r->pivot->valor_aplicado);
    }

    public function scopePago(Builder $query): Builder
    {
        return $query->where('situacao', 'PAGO');
    }

    public function scopeEmAberto(Builder $query): Builder
    {
        return $query->whereIn('situacao', ['ABERTO', 'PENDENTE']);
    }

    /**
     * Vencido = ABERTO com a data de vencimento já passada (status derivado, não armazenado).
     * PENDENTE é um status definido manualmente e NÃO é considerado vencido.
     */
    public function scopeVencido(Builder $query): Builder
    {
        return $query->where('situacao', 'ABERTO')
            ->whereNotNull('data_vencimento')
            ->whereDate('data_vencimento', '<', today());
    }

    /** Indica, por linha, se o pagamento está vencido (apenas ABERTO). */
    public function getEstaVencidoAttribute(): bool
    {
        return $this->situacao === 'ABERTO'
            && $this->data_vencimento
            && $this->data_vencimento->lt(today());
    }

    /** Apenas os tipos que entram no caixa (entradas reais). */
    public function scopeEntrada(Builder $query): Builder
    {
        return $query->whereIn('id_tipo_pagamento', TipoPagamento::TIPOS_ENTRADA);
    }

    /**
     * Parcelas elegíveis para a conciliação automática (FIFO): em aberto e que
     * NÃO estejam reservadas por um acordo ainda em aberto.
     */
    public function scopeDisponivelParaConciliacao(Builder $query): Builder
    {
        return $query->emAberto()
            ->whereDoesntHave('acordos', fn (Builder $q) => $q->where('situacao', Acordo::SITUACAO_ABERTO));
    }
}

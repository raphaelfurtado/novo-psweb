<?php

namespace App\Models;

use App\Models\Concerns\FiltraPeriodo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Saída (despesa). Quando id_funcionario está preenchido, é um lançamento de folha;
 * caso contrário é uma despesa da associação.
 *
 * Quirk de schema: id_tipo_pagamento, id_forma_pagamento e id_tipo_saida
 * referenciam o `codigo` das respectivas tabelas (não o id).
 */
class Saida extends Model
{
    use FiltraPeriodo;

    protected $table = 'saida';

    public $timestamps = false;

    protected $fillable = [
        'id_recebedor', 'id_funcionario', 'id_tipo_pagamento', 'id_forma_pagamento',
        'id_tipo_saida', 'data_pagamento', 'referencia', 'valor', 'situacao',
        'observacao', 'data_insert',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pagamento' => 'datetime',
        'data_insert' => 'datetime',
    ];

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'id_funcionario');
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

    public function tipoSaida(): BelongsTo
    {
        return $this->belongsTo(TipoSaida::class, 'id_tipo_saida', 'codigo');
    }

    public function comprovantes(): HasMany
    {
        return $this->hasMany(Anexo::class, 'identifier')->whereIn('form', ['SAIDA', 'PAGAMENTO_FUNC']);
    }

    /** Lançamentos de folha (com funcionário). */
    public function scopeDeFolha(Builder $query): Builder
    {
        return $query->whereNotNull('id_funcionario');
    }

    /** Despesas da associação (sem funcionário). */
    public function scopeDespesa(Builder $query): Builder
    {
        return $query->whereNull('id_funcionario');
    }
}

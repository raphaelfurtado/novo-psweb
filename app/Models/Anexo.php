<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Anexo (tabela `files`). `stored_name` é o caminho relativo dentro do disco
 * de uploads (estrutura AAAA/MM/arquivo, com fallback para a raiz nos antigos).
 *
 * type_anex: 1=ASSOCIAÇÃO, 2=MORADOR, 3=PRESTAÇÃO DE CONTAS.
 * form: PAGAMENTO, SAIDA, PAGAMENTO_FUNC, ANEXO.
 */
class Anexo extends Model
{
    protected $table = 'files';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'original_name', 'stored_name', 'mime_type', 'size', 'type_anex',
        'id_morador', 'id_funcionario', 'subject', 'form', 'identifier',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const TIPO_ASSOCIACAO = 1;
    public const TIPO_MORADOR = 2;
    public const TIPO_PRESTACAO_CONTAS = 3;

    public function morador()
    {
        return $this->belongsTo(User::class, 'id_morador');
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'id_funcionario');
    }
}

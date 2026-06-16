<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Funcionario extends Model
{
    protected $table = 'funcionarios';

    const CREATED_AT = 'data_criacao';
    const UPDATED_AT = 'ultima_atualizacao';

    protected $fillable = [
        'nome_completo', 'data_nascimento', 'cpf', 'rg', 'endereco_completo',
        'complemento', 'cidade', 'estado', 'cep', 'telefone_whatsapp', 'salario',
        'nome_titular_conta', 'banco', 'agencia', 'numero_conta', 'tipo_conta',
        'chave_pix', 'observacao', 'usuario_criacao', 'usuario_atualizacao',
    ];

    protected $casts = [
        'salario' => 'decimal:2',
        'data_nascimento' => 'date',
        'data_criacao' => 'datetime',
        'ultima_atualizacao' => 'datetime',
    ];

    public const TIPOS_CONTA = [
        '01' => 'Conta Corrente',
        '02' => 'Conta Poupança',
        '03' => 'Conta Salário',
    ];

    public function saidas(): HasMany
    {
        return $this->hasMany(Saida::class, 'id_funcionario');
    }
}

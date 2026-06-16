<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Morador / usuário do sistema. Tabela legada `users` (login por telefone).
 * Sem timestamps; sem coluna remember_token.
 */
class User extends Authenticatable
{
    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'telefone', 'telefone_2', 'cpf', 'aniversario', 'nome',
        'possui_acordo', 'acordo', 'senha', 'role', 'consent_policy',
    ];

    protected $hidden = ['senha'];

    /** A senha vive na coluna `senha` (bcrypt, compatível com Hash::check). */
    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    /**
     * Nome da coluna de senha. Sem isto, o Laravel tentaria gravar em `password`
     * (ex.: no re-hash automático de login) e quebraria — a coluna real é `senha`.
     */
    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    // A tabela não tem remember_token — desabilita a persistência do "lembrar-me".
    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        //
    }

    public function getRememberTokenName()
    {
        return null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function endereco(): HasOne
    {
        return $this->hasOne(Endereco::class, 'id_usuario');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class, 'id_usuario');
    }

    public function acordos(): HasMany
    {
        return $this->hasMany(Acordo::class, 'id_usuario');
    }

    public function getPrimeiroNomeAttribute(): string
    {
        return strtok((string) $this->nome, ' ');
    }

    /** Guarda o CPF apenas com dígitos (chave do casamento da conciliação). */
    public function setCpfAttribute(?string $value): void
    {
        $digitos = $value !== null ? preg_replace('/\D/', '', $value) : null;
        $this->attributes['cpf'] = $digitos ?: null;
    }

    /** "12345678901" => "123.456.789-01" (vazio quando não há CPF). */
    public function getCpfFormatadoAttribute(): string
    {
        $cpf = (string) $this->cpf;

        return strlen($cpf) === 11
            ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf)
            : $cpf;
    }
}

<?php

namespace App\Models;

use App\Enums\CategoriaProfissional;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuário do sistema — profissional de saúde, gestor do NSP ou administrador.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'nome',
        'email',
        'password',
        'matricula',
        'registro_conselho',
        'categoria_profissional',
        'cargo',
        'setor_id',
        'telefone',
        'ativo',
        'ultimo_acesso_em',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acesso_em' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
            'categoria_profissional' => CategoriaProfissional::class,
        ];
    }

    // ── Relacionamentos ────────────────────────────────────────────────

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    // ── Escopos ────────────────────────────────────────────────────────

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}

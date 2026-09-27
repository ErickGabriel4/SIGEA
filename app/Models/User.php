<?php

namespace App\Models;

use App\Enums\CategoriaProfissional;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuário do sistema — profissional de saúde, gestor do NSP ou administrador.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

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

    /** Configuração da trilha de auditoria (spatie/laravel-activitylog). */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nome', 'email', 'matricula', 'cargo', 'setor_id', 'ativo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('usuario');
    }

    /**
     * Alias de leitura para `nome`: o AdminLTE (menu do usuário, incluindo
     * o botão "Sair") espera o atributo padrão `name` do Laravel.
     */
    protected function name(): Attribute
    {
        return Attribute::make(get: fn () => $this->nome);
    }

    // ── Relacionamentos ────────────────────────────────────────────────

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    public function eventosNotificados(): HasMany
    {
        return $this->hasMany(EventoAdverso::class, 'notificante_id');
    }

    public function eventosGerenciados(): HasMany
    {
        return $this->hasMany(EventoAdverso::class, 'gestor_responsavel_id');
    }

    public function investigacoes(): HasMany
    {
        return $this->hasMany(Investigacao::class, 'responsavel_id');
    }

    public function acoesResponsavel(): HasMany
    {
        return $this->hasMany(AcaoCorretiva::class, 'responsavel_id');
    }

    // ── Escopos ────────────────────────────────────────────────────────

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}

<?php

namespace App\Models;

use App\Enums\TipoSetor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Setor hospitalar — local de ocorrência dos eventos e lotação dos usuários.
 */
class Setor extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'setores';

    protected $fillable = [
        'nome',
        'sigla',
        'tipo',
        'responsavel_id',
        'leitos',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoSetor::class,
            'ativo' => 'boolean',
            'leitos' => 'integer',
        ];
    }

    // ── Relacionamentos ────────────────────────────────────────────────

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoAdverso::class, 'setor_ocorrencia_id');
    }

    // ── Escopos ────────────────────────────────────────────────────────

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nome', 'sigla', 'tipo', 'responsavel_id', 'leitos', 'ativo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('setor');
    }
}

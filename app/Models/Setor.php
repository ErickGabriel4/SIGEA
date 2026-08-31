<?php

namespace App\Models;

use App\Enums\TipoSetor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Setor hospitalar — local de ocorrência dos eventos e lotação dos usuários.
 */
class Setor extends Model
{
    use HasFactory, SoftDeletes;

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

    // ── Escopos ────────────────────────────────────────────────────────

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}

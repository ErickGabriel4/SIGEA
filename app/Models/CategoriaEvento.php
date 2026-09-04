<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Categoria de evento — taxonomia hierárquica de 2 níveis alinhada à ICPS/WHO.
 */
class CategoriaEvento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categorias_evento';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'categoria_pai_id',
        'codigo_icps',
        'notificavel_anvisa',
        'exige_investigacao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'notificavel_anvisa' => 'boolean',
            'exige_investigacao' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    // ── Relacionamentos ────────────────────────────────────────────────

    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'categoria_pai_id');
    }

    public function filhas(): HasMany
    {
        return $this->hasMany(self::class, 'categoria_pai_id')->orderBy('codigo');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoAdverso::class, 'categoria_evento_id');
    }

    // ── Escopos ────────────────────────────────────────────────────────

    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeRaizes($query)
    {
        return $query->whereNull('categoria_pai_id')->orderBy('codigo');
    }

    /** Nome completo com o código, ex.: "MED.01 — Dose incorreta". */
    public function getNomeCompletoAttribute(): string
    {
        return "{$this->codigo} — {$this->nome}";
    }
}

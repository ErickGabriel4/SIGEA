<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Comentário polimórfico. interno=true é visível apenas a gestores/administradores.
 */
class Comentario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comentarios';

    protected $fillable = [
        'comentavel_type',
        'comentavel_id',
        'user_id',
        'corpo',
        'interno',
    ];

    protected function casts(): array
    {
        return [
            'interno' => 'boolean',
        ];
    }

    public function comentavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Escopo de visibilidade: notificador não vê comentários internos. */
    public function scopeVisiveisPara($query, User $usuario)
    {
        if ($usuario->can('evento.ver_todos')) {
            return $query;
        }

        return $query->where('interno', false);
    }
}

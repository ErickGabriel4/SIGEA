<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro imutável da trilha do workflow — sem update e sem delete.
 */
class EventoHistorico extends Model
{
    protected $table = 'evento_historico';

    /** Somente created_at (registro imutável). */
    public const UPDATED_AT = null;

    protected $fillable = [
        'evento_adverso_id',
        'user_id',
        'status_anterior',
        'status_novo',
        'acao',
        'observacao',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoAdverso::class, 'evento_adverso_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Impede alteração e exclusão de registros do histórico. */
    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}

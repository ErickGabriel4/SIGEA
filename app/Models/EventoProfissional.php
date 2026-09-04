<?php

namespace App\Models;

use App\Enums\CategoriaProfissional;
use App\Enums\PapelProfissional;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Profissional envolvido ou testemunha de um evento adverso.
 */
class EventoProfissional extends Model
{
    use HasFactory;

    protected $table = 'evento_profissionais';

    protected $fillable = [
        'evento_adverso_id',
        'user_id',
        'nome_externo',
        'categoria_profissional',
        'papel',
    ];

    protected function casts(): array
    {
        return [
            'categoria_profissional' => CategoriaProfissional::class,
            'papel' => PapelProfissional::class,
        ];
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoAdverso::class, 'evento_adverso_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Nome exibível: usuário interno ou nome externo. */
    public function getNomeAttribute(): string
    {
        return $this->usuario?->nome ?? $this->nome_externo ?? '—';
    }
}

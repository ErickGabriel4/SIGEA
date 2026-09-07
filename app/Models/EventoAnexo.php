<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anexo de evento — arquivo em storage privado, servido por rota autenticada.
 */
class EventoAnexo extends Model
{
    use HasFactory;

    protected $table = 'evento_anexos';

    protected $fillable = [
        'evento_adverso_id',
        'user_id',
        'nome_original',
        'caminho',
        'mime',
        'tamanho',
    ];

    protected function casts(): array
    {
        return [
            'tamanho' => 'integer',
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

    /** Tamanho legível (KB/MB). */
    public function getTamanhoLegivelAttribute(): string
    {
        if ($this->tamanho >= 1048576) {
            return number_format($this->tamanho / 1048576, 1, ',', '.') . ' MB';
        }

        return number_format($this->tamanho / 1024, 0, ',', '.') . ' KB';
    }
}

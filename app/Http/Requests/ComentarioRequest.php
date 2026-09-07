<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação de comentários no evento.
 */
class ComentarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorização pela Policy (comentar) no controller
    }

    public function rules(): array
    {
        return [
            'corpo' => ['required', 'string', 'max:3000'],
            'interno' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'corpo' => 'comentário',
            'interno' => 'comentário interno',
        ];
    }
}

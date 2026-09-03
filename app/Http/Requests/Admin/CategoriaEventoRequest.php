<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação de cadastro/edição de categorias de evento (T19).
 */
class CategoriaEventoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('categoria.gerenciar');
    }

    public function rules(): array
    {
        $categoriaId = $this->route('categoria')?->id;

        return [
            'codigo' => ['required', 'string', 'max:15', Rule::unique('categorias_evento', 'codigo')->ignore($categoriaId)],
            'nome' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'categoria_pai_id' => [
                'nullable', 'integer',
                Rule::exists('categorias_evento', 'id')->whereNull('categoria_pai_id'),
                Rule::notIn([$categoriaId]),
            ],
            'codigo_icps' => ['nullable', 'string', 'max:20'],
            'notificavel_anvisa' => ['sometimes', 'boolean'],
            'exige_investigacao' => ['sometimes', 'boolean'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'nome' => 'nome',
            'descricao' => 'descrição',
            'categoria_pai_id' => 'categoria-raiz',
            'codigo_icps' => 'código ICPS',
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_pai_id.exists' => 'A categoria-pai deve ser uma categoria-raiz (taxonomia de 2 níveis).',
        ];
    }
}

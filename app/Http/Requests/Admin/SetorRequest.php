<?php

namespace App\Http\Requests\Admin;

use App\Enums\TipoSetor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação de cadastro/edição de setores (T18).
 */
class SetorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('setor.gerenciar');
    }

    public function rules(): array
    {
        $setorId = $this->route('setore')?->id ?? $this->route('setor')?->id;

        return [
            'nome' => ['required', 'string', 'max:120'],
            'sigla' => ['required', 'string', 'max:15', Rule::unique('setores', 'sigla')->ignore($setorId)],
            'tipo' => ['required', Rule::enum(TipoSetor::class)],
            'responsavel_id' => ['nullable', 'integer', 'exists:users,id'],
            'leitos' => ['nullable', 'integer', 'between:0,2000'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'sigla' => 'sigla',
            'tipo' => 'tipo de setor',
            'responsavel_id' => 'responsável',
            'leitos' => 'número de leitos',
        ];
    }
}

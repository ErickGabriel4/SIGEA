<?php

namespace App\Http\Requests\Admin;

use App\Enums\CategoriaProfissional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validação de cadastro/edição de usuários (T17).
 */
class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('usuario.gerenciar');
    }

    public function rules(): array
    {
        $usuarioId = $this->route('usuario')?->id;

        return [
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuarioId)],
            'password' => [
                $usuarioId ? 'nullable' : 'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'matricula' => ['nullable', 'string', 'max:30', Rule::unique('users', 'matricula')->ignore($usuarioId)],
            'registro_conselho' => ['nullable', 'string', 'max:30'],
            'categoria_profissional' => ['required', Rule::enum(CategoriaProfissional::class)],
            'cargo' => ['nullable', 'string', 'max:100'],
            'setor_id' => ['nullable', 'integer', 'exists:setores,id'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'perfil' => ['required', Rule::exists('roles', 'name')],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'email' => 'e-mail',
            'password' => 'senha',
            'matricula' => 'matrícula',
            'registro_conselho' => 'registro de conselho',
            'categoria_profissional' => 'categoria profissional',
            'cargo' => 'cargo',
            'setor_id' => 'setor',
            'telefone' => 'telefone',
            'perfil' => 'perfil de acesso',
        ];
    }
}

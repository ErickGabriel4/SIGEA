<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Perfil do usuário autenticado (T23) — dados pessoais e troca de senha.
 */
class PerfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('perfil.edit', ['usuario' => $request->user()]);
    }

    public function update(Request $request)
    {
        $usuario = $request->user();

        $dados = $request->validate(
            [
                'nome' => ['required', 'string', 'max:150'],
                'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario->id)],
                'telefone' => ['nullable', 'string', 'max:20'],
            ],
            [],
            ['nome' => 'nome', 'email' => 'e-mail', 'telefone' => 'telefone']
        );

        $usuario->update($dados);

        return back()->with('sucesso', 'Perfil atualizado.');
    }

    public function atualizarSenha(Request $request)
    {
        $request->validate(
            [
                'senha_atual' => ['required', 'current_password'],
                'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            ],
            ['senha_atual.current_password' => 'A senha atual não confere.'],
            ['senha_atual' => 'senha atual', 'password' => 'nova senha']
        );

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('sucesso', 'Senha alterada com sucesso.');
    }
}

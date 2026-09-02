<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UsuarioRequest;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * Gestão de usuários e perfis (T17 — UC18).
 * Atende ao objetivo específico (e) do TCC.
 */
class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = User::query()
            ->with(['setor', 'roles'])
            ->when($request->filled('busca'), function ($q) use ($request) {
                $busca = $request->string('busca');
                $q->where(fn ($sub) => $sub
                    ->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%")
                    ->orWhere('matricula', 'like', "%{$busca}%"));
            })
            ->when($request->filled('perfil'), fn ($q) => $q->role($request->string('perfil')))
            ->when($request->filled('setor_id'), fn ($q) => $q->where('setor_id', $request->integer('setor_id')))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'perfis' => Role::orderBy('name')->get(),
            'setores' => Setor::ativos()->orderBy('nome')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.usuarios.create', [
            'perfis' => Role::orderBy('name')->get(),
            'setores' => Setor::ativos()->orderBy('nome')->get(),
        ]);
    }

    public function store(UsuarioRequest $request)
    {
        $dados = $request->validated();
        $perfil = $dados['perfil'];
        unset($dados['perfil']);

        $usuario = User::create($dados);
        $usuario->syncRoles([$perfil]);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('sucesso', "Usuário {$usuario->nome} criado com o perfil {$perfil}.");
    }

    public function edit(User $usuario)
    {
        return view('admin.usuarios.edit', [
            'usuario' => $usuario,
            'perfis' => Role::orderBy('name')->get(),
            'setores' => Setor::ativos()->orderBy('nome')->get(),
        ]);
    }

    public function update(UsuarioRequest $request, User $usuario)
    {
        $dados = $request->validated();
        $perfil = $dados['perfil'];
        unset($dados['perfil']);

        if (blank($dados['password'] ?? null)) {
            unset($dados['password']);
        }

        $usuario->update($dados);
        $usuario->syncRoles([$perfil]);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('sucesso', "Usuário {$usuario->nome} atualizado.");
    }

    /** Inativação lógica — nunca exclusão física de usuário com histórico. */
    public function destroy(Request $request, User $usuario)
    {
        abort_if($usuario->id === $request->user()->id, 403, 'Você não pode excluir a si mesmo.');

        $usuario->update(['ativo' => false]);
        $usuario->delete();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('sucesso', "Usuário {$usuario->nome} removido (exclusão lógica).");
    }

    /** Alterna ativo/inativo sem excluir. */
    public function alternarStatus(Request $request, User $usuario)
    {
        abort_if($usuario->id === $request->user()->id, 403, 'Você não pode inativar a si mesmo.');

        $usuario->update(['ativo' => ! $usuario->ativo]);

        return back()->with('sucesso', $usuario->ativo
            ? "Acesso de {$usuario->nome} reativado."
            : "Acesso de {$usuario->nome} suspenso.");
    }
}

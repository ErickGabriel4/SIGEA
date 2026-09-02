<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\PerfilSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /** Semeia os perfis/permissões do RBAC (necessário na maioria dos testes). */
    protected function semearPerfis(): void
    {
        $this->seed(PerfilSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function criarNotificador(array $atributos = []): User
    {
        $usuario = User::factory()->create($atributos);
        $usuario->assignRole('notificador');

        return $usuario;
    }

    protected function criarGestor(array $atributos = []): User
    {
        $usuario = User::factory()->create($atributos);
        $usuario->assignRole('gestor_seguranca');

        return $usuario;
    }

    protected function criarAdministrador(array $atributos = []): User
    {
        $usuario = User::factory()->create($atributos);
        $usuario->assignRole('administrador');

        return $usuario;
    }
}

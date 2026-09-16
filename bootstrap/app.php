<?php

use App\Http\Middleware\RegistrarUltimoAcesso;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Atrás do proxy do Railway: confia nos cabeçalhos X-Forwarded-* para que
        // a aplicação reconheça o esquema HTTPS e gere URLs e cookies corretos.
        $middleware->trustProxies(at: '*');

        // Cabeçalhos de segurança e registro de último acesso em todas as rotas web
        $middleware->web(append: [
            SecurityHeaders::class,
            RegistrarUltimoAcesso::class,
        ]);

        // Aliases do spatie/laravel-permission (RBAC)
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

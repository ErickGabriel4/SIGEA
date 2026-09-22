<?php

namespace App\Providers;

use App\Models\EventoAdverso;
use App\Observers\EventoAdversoObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registro de serviços da aplicação.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicialização da aplicação: observers e ajustes globais.
     */
    public function boot(): void
    {
        EventoAdverso::observe(EventoAdversoObserver::class);

        // Paginação com markup Bootstrap 4 (AdminLTE 3)
        Paginator::useBootstrapFour();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}

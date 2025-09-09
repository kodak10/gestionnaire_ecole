<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Pagination Bootstrap
        Paginator::useBootstrap();

        View::composer('*', function ($view) {
            $user = Auth::user();
            $ecole = null;

            // Vérifie que $user est bien une instance de ton modèle User
            if ($user instanceof User) {
                $ecole = $user->ecole; // relation ecole
            }

            $view->with([
                'user' => $user,
                'ecole' => $ecole,
            ]);
        });
    }

}

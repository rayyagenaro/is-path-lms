<?php

namespace App\Providers;

use App\Database\Connectors\NeonPostgresConnector;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('db.connector.pgsql', fn () => new NeonPostgresConnector);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\View::composer(['dashboard.student', 'competencies.index'], function ($view) {
            $view->with('competencyTotal', \Illuminate\Support\Facades\DB::table('competencies')->where('is_active', true)->count());
        });
    }
}

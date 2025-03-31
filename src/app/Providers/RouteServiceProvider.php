<?php

namespace App\Providers;

use App\Http\Controllers\ApiAuthController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot()
    {
        $this->configureRateLimiting();
        $this->configureMiddleware();
        $this->mapRoutes();
    }

    /**
     * Configure the middleware for the application.
     */
    protected function configureMiddleware()
    {
        // Adiciona o middleware ao grupo 'api' se quiser aplicá-lo globalmente
        Route::middlewareGroup('api', [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // \App\Http\Middleware\ApiAuthenticate::class, // Descomente para proteger toda a API
        ]);

        // Registra o alias do middleware
        Route::aliasMiddleware('auth.api', \App\Http\Middleware\ApiSecurity::class);
    }

    /**
     * Define the routes for the application.
     */
    protected function mapRoutes()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapAuthRoutes();
    }

    /**
     * Define the "api" routes for the application.
     */
    protected function mapApiRoutes()
    {
        Route::middleware(['api', 'auth.api']) // Middleware aplicado aqui
            ->prefix('api')
            ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "web" routes for the application.
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the authentication routes for the application.
     */
    protected function mapAuthRoutes()
    {
        Route::middleware('api')
            ->prefix('api/auth')
            ->group(function () {
                Route::post('login', [ApiAuthController::class, 'login'])
                    ->withoutMiddleware('auth.api'); // Garante que login não requer autenticação
                
                Route::post('renew', [ApiAuthController::class, 'renewToken'])
                    ->middleware('auth.api');
                
                Route::post('logout', [ApiAuthController::class, 'logout'])
                    ->middleware('auth.api');
            });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Adicione um rate limiting específico para autenticação
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
<?php

namespace AlvinFadli\AccessLock;

use AlvinFadli\AccessLock\Console\Commands\SetAccessLockPasswordCommand;
use AlvinFadli\AccessLock\Http\Middleware\AccessLockApiMiddleware;
use AlvinFadli\AccessLock\Http\Middleware\AccessLockMiddleware;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AccessLockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/access-lock.php',
            'access-lock'
        );
    }

    public function boot(): void
    {
        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerCommands();
        $this->registerPublishables();
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('access.lock', AccessLockMiddleware::class);
        $router->aliasMiddleware('access.lock.api', AccessLockApiMiddleware::class);
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'access-lock');
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SetAccessLockPasswordCommand::class,
            ]);
        }
    }

    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/access-lock.php' => config_path('access-lock.php'),
            ], 'access-lock-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/access-lock'),
            ], 'access-lock-views');

            $this->publishes([
                __DIR__.'/../config/access-lock.php' => config_path('access-lock.php'),
                __DIR__.'/../resources/views' => resource_path('views/vendor/access-lock'),
            ], 'access-lock');
        }
    }
}

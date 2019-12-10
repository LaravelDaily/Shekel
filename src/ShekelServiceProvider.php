<?php

namespace Shekel;

use Illuminate\Support\ServiceProvider;
use Shekel\Models\Plan;
use Shekel\Observers\PlanObserver;

class ShekelServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->loadViewsFrom(__DIR__ . '/../views', 'Shekel');
        $this->mergeConfigFrom(__DIR__ . '/../config/shekel.php', 'shekel');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../migrations');
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

    }

    public function boot()
    {
        Plan::observe(PlanObserver::class);
        $this->publish();
    }

    public function publish()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/shekel.php.php' => $this->app->configPath('shekel.php'),
            ], 'shekel-config');
            if (!Shekel::$disableMigrations) {
                $this->publishes([
                    __DIR__ . '/../migrations' => $this->app->databasePath('migrations'),
                ], 'shekel-migrations');
            }
            $this->publishes([
                __DIR__ . '/../views' => $this->app->resourcePath('views/vendor/shekel'),
            ], 'shekel-views');
        }
    }

}
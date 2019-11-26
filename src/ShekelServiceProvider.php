<?php

namespace Shekel;

use Illuminate\Support\ServiceProvider;

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
        if (config('shekel.stripe.secret_key')) {
            Shekel::activatePaymentProvider('stripe');
        }

    }

}
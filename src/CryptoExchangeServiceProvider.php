<?php

namespace Alikhedmati\CryptoExchange;

use Alikhedmati\CryptoExchange\Contracts\CryptoExchangeInterface;
use Illuminate\Support\ServiceProvider;

class CryptoExchangeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '../config/crypto-exchange.php',
            'crypto-exchange'
        );

        $this->app->bind(CryptoExchangeInterface::class, fn() => new CryptoExchange());

    }

    public function boot()
    {
        
    }

    protected function offerPublishing()
    {
        $this->publishes([
            __DIR__ .'../config/crypto-exchange.php'   =>   config_path('crypto-exchange.php')
        ], 'config');
    }
}
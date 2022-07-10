<?php

namespace Alikhedmati\CryptoExchange\Facades;

use Alikhedmati\CryptoExchange\Contracts\CryptoExchangeInterface;
use Illuminate\Support\Facades\Facade;

class CryptoExchangeFacade extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return CryptoExchangeInterface::class;
    }
}
<?php

namespace Alikhedmati\CryptoExchange\Contracts;

use Illuminate\Support\Collection;

interface CryptoExchangeInterface
{
    public function getAllMarkets(): Collection;
}
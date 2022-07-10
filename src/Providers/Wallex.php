<?php

namespace Alikhedmati\CryptoExchange\Providers;

use Alikhedmati\CryptoExchange\Contracts\CryptoExchangeProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

class Wallex implements CryptoExchangeProviderInterface
{
    /**
     * Base endpoint.
     */

    const mainnetRestApiBase = 'https://api.wallex.ir/v1/';

    /**
     * @var string
     */

    private string $restApiBase;

    /**
     * Wallex Class Constructor.
     */

    public function __construct()
    {
        $this->restApiBase = self::mainnetRestApiBase;
    }

    /**
     * @param bool $isAuthenticated
     * @return Client
     */

    private function client(bool $isAuthenticated = false): Client
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent'    =>  'TraderBot/' . config('app.name')
        ];

        if ($isAuthenticated){

            $headers['x-api-key'] = config('settings.wallex-api-key');

        }

        return new Client([
            'base_uri' => $this->restApiBase,
            'headers' => $headers,
            'http_errors' => false
        ]);
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getAllMarkets(): Collection
    {
        return collect(json_decode($this->client()->get('markets')->getBody()->getContents())->result->symbols)
            ->transform(fn($value, $key) => $value->stats->bidPrice)
            ->mapWithKeys(fn($value, $key) => [str_contains($key, 'TMN') ? str_replace('TMN', 'IRT', $key) : $key => $value]);
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getProfile(): Collection
    {
        return collect(json_decode($this->client(true)->get('account/profile')->getBody()->getContents()));
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getAllOrders(): Collection
    {
        return collect(json_decode($this->client(true)->get('account/trades')->getBody()->getContents()));
    }
}

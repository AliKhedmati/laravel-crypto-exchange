<?php

namespace Alikhedmati\CryptoExchange\Providers;

use Alikhedmati\CryptoExchange\Contracts\CryptoExchangeInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class Nobitex implements CryptoExchangeInterface
{
    /**
     * Base endpoint.
     */

    const mainnetRestApiBase = 'https://api.nobitex.ir/';

    /**
     * @var string
     */

    private string $restApiBase;

    /**
     * Nobitex Class Constructor.
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

            $headers['Authorization'] = 'Token ' . $this->getAccessToken();

        }

        return new Client([
            'base_uri' => $this->restApiBase,
            'headers' =>    $headers,
            'http_errors' => false
        ]);
    }

    /**
     * @return string
     */

    private function getAccessToken(): string
    {
        /**
         * Check if redis has access-token or not.
         */

        $accessToken = Redis::get('nobitex-access-token');

        if ($accessToken){

            /**
             * todo: Make a call to /profile endpoint to ensure that access token is valid.
             */

            /**
             * Return access-token.
             */

            return decrypt($accessToken);

        }

        /**
         * Fetch new access-token.
         */

        try {

            $accessToken = $this->authenticate();

        } catch (GuzzleException|Exception $exception){

            Log::critical($exception);

        }

        /**
         * Store access-token in redis.
         */

        Redis::set('nobitex-access-token', encrypt($accessToken));

        /**
         * Return access-token.
         */

        return $accessToken;
    }

    /**
     * @return string
     * @throws GuzzleException
     * @throws Exception
     */

    private function authenticate(): string
    {
        /**
         * Make API call.
         */

        $request = $this->client()->post('auth/login/', [
            'json'  =>  [
                'username'  =>  config('settings.nobitex-username'),
                'password'  =>  config('settings.nobitex-password'),
                'remember'  =>  'yes',
                'captcha'   =>  'api'
            ],
        ]);

        /**
         * Handle unsuccessful login attempt.
         */

        if ($request->getStatusCode() != 200){

            throw new Exception(json_decode($request->getBody()->getContents()));

        }

        /**
         * Get token.
         */

        return json_decode($request->getBody()->getContents())?->key;
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getAllMarkets(): Collection
    {
        return collect(json_decode($this->client()->get('v2/orderbook/all')->getBody()->getContents()))
            ->except('status')
            ->map(fn($i) => collect($i)->only('lastTradePrice')->values()->first())
            ->reject(fn($i) => !isset($i[0][0]))
            ->map(fn($value, $key) => str_contains($key, 'IRT') ? (string)($value / 10) : $value);
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getProfile(): Collection
    {
        return collect(json_decode($this->client(true)->get('users/profile')->getBody()->getContents()));
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getAllOrders(): Collection
    {
        return collect(json_decode($this->client(true)->post('market/orders/list', [
            'json'  =>  [
                'status'    =>  'all',
                'details'   =>  2,
            ],
        ])->getBody()->getContents()))?->only('orders');
    }

    /**
     * @param int $orderId
     * @return Collection
     * @throws GuzzleException
     */

    public function getOrder(int $orderId): Collection
    {
        return collect(json_decode($this->client(true)->post('market/orders/status', [
            'json'  =>  [
                'id'    =>  $orderId
            ],
        ])->getBody()->getContents()));
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getLoginAttempts(): Collection
    {
        return collect(json_decode($this->client(true)->get('users/login-attempts')->getBody()->getContents()));
    }

    public function storeOrder(string $market, float $quantity, string $side, string $execution): Collection
    {
        /**
         * Cast market.
         */

        $market = explode('-', strtolower($market));

        if ($market[1] === 'IRT'){

            $market[1] === 'rls';

            $quantity = $quantity / 10;

        }

        /**
         * Create
         */

        $order = [
            'type'  =>  $side, // buy, sell.
            'execution' =>  $execution, // market, limit.
            'mode'  =>  'default',
        ];

        $request = $this->client(true)->post('market/orders/all', [
            'json'  =>  $order
        ]);
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getAllWallets(): Collection
    {
        return collect(json_decode($this->client(true)->post('users/wallets/list')->getBody()->getContents()))?->only('wallets');
    }
}

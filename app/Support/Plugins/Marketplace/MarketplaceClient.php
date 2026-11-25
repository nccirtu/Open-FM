<?php

namespace App\Support\Plugins\Marketplace;

use App\Services\AppSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MarketplaceClient
{
    public const CATALOG_CACHE_KEY = 'plugins.marketplace.catalog';

    public function configured(): bool
    {
        return filled(config('plugins.marketplace.url'));
    }

    /**
     * @return Collection<string, array{slug: string, package: string, name?: string, description?: string, version?: string, price?: array{amount: int|float, currency: string}}>
     */
    public function catalog(): Collection
    {
        if (! $this->configured()) {
            return collect();
        }

        $plugins = Cache::get(self::CATALOG_CACHE_KEY);

        if (! is_array($plugins)) {
            try {
                $plugins = (array) $this->request()->get('/api/v1/plugins')->throw()->json('data', []);
                Cache::put(self::CATALOG_CACHE_KEY, $plugins, now()->addMinutes(10));
            } catch (Throwable) {
                return collect();
            }
        }

        return collect($plugins)
            ->filter(fn ($plugin): bool => is_array($plugin) && filled($plugin['slug'] ?? null) && filled($plugin['package'] ?? null))
            ->keyBy('slug');
    }

    public function issueLicense(string $slug): string
    {
        try {
            $response = $this->request()->post('/api/v1/licenses', [
                'installation_id' => self::installationId(),
                'plugin' => $slug,
                'app_url' => config('app.url'),
            ])->throw();
        } catch (Throwable $exception) {
            throw new MarketplaceException(__('plugins.installer.errors.marketplace', ['message' => $exception->getMessage()]), previous: $exception);
        }

        $key = $response->json('license_key');

        if (! is_string($key) || $key === '') {
            throw new MarketplaceException(__('plugins.installer.errors.marketplace', ['message' => 'license_key missing']));
        }

        if (is_string($token = $response->json('repository_token')) && $token !== '') {
            AppSettings::set(['marketplace_repository_token' => Crypt::encryptString($token)]);
        }

        return $key;
    }

    public function repositoryToken(): ?string
    {
        $encrypted = AppSettings::get('marketplace_repository_token');

        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return null;
        }
    }

    public function repositoryUrl(): string
    {
        return rtrim((string) config('plugins.marketplace.url'), '/').'/composer';
    }

    public function host(): string
    {
        return (string) parse_url((string) config('plugins.marketplace.url'), PHP_URL_HOST);
    }

    public static function installationId(): string
    {
        $id = AppSettings::get('installation_id');

        if (! is_string($id) || $id === '') {
            $id = (string) Str::uuid7();
            AppSettings::set(['installation_id' => $id]);
        }

        return $id;
    }

    protected function request(): PendingRequest
    {
        $url = (string) config('plugins.marketplace.url');

        if (! str_starts_with($url, 'https://') && ! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Die Shop-URL muss https verwenden.');
        }

        return Http::baseUrl(rtrim($url, '/'))
            ->acceptJson()
            ->timeout((int) config('plugins.marketplace.timeout', 15));
    }
}

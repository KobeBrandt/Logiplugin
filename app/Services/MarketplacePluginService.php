<?php

namespace App\Services;

use App\Exceptions\MarketplaceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketplacePluginService
{
    public function list(array $filters = []): array
    {
        $plugins = $this->plugins();

        return array_values(array_filter($plugins, function (array $plugin) use ($filters): bool {
            $search = $filters['search'] ?? null;

            if ($search !== null && $search !== '') {
                $name = (string) ($plugin['name'] ?? '');
                $displayName = (string) ($plugin['displayName'] ?? '');

                if (stripos($name, $search) === false && stripos($displayName, $search) === false) {
                    return false;
                }
            }

            if (($filters['platform'] ?? null) !== null
                && ! in_array($filters['platform'], $plugin['platforms'] ?? [], true)) {
                return false;
            }

            return ($filters['category'] ?? null) === null
                || in_array($filters['category'], $plugin['categories'] ?? [], true);
        }));
    }

    public function find(string $name): ?array
    {
        foreach ($this->plugins() as $plugin) {
            if (strcasecmp($plugin['name'], $name) === 0) {
                return array_merge($plugin, $this->details($plugin['name']));
            }
        }

        return null;
    }

    private function plugins(): array
    {
        return Cache::remember('marketplace.plugins', $this->cacheTtl(), function (): array {
            $data = $this->getNextData('/_next/data/%s/plugins/en/4.json');
            $rawPlugins = $data['pageProps']['initialState']['pluginList']['plugins'] ?? null;

            if (! is_array($rawPlugins) || $rawPlugins === []) {
                throw new MarketplaceException('No plugins found in the marketplace response.');
            }

            $downloads = $this->getJson($this->marketplaceUrl('/api/downloads'))['plugins'] ?? [];

            return array_map(fn (array $plugin): array => $this->mapPlugin($plugin, $downloads), $rawPlugins);
        });
    }

    private function details(string $name): array
    {
        return Cache::remember('marketplace.plugin.details.'.sha1($name), $this->cacheTtl(), function () use ($name): array {
            $data = $this->getNextData(
                '/_next/data/%s/plugin/%s/en.json',
                [rawurlencode($name)],
                ['language' => 'en', 'pluginName' => $name],
            );
            $plugin = $data['pageProps']['initialState']['pluginDetails']['data'] ?? null;

            if (! is_array($plugin)) {
                throw new MarketplaceException('Detailed plugin data not found.');
            }

            return [
                'author' => [
                    'name' => $plugin['author']['name'] ?? null,
                    'supportUrl' => $plugin['author']['supportUrl'] ?? null,
                ],
                'homeUrl' => $plugin['homeUrl'] ?? null,
                'requirements' => array_values(array_map(
                    fn (array $faq): mixed => $faq['answer'] ?? null,
                    array_filter(
                        $plugin['faqs'] ?? [],
                        fn (array $faq): bool => stripos((string) ($faq['question'] ?? ''), 'requirement') !== false,
                    ),
                )),
                'versions' => array_map(fn (array $package): array => [
                    'version' => $package['version'] ?? null,
                    'fileUrl' => $package['fileUrl'] ?? null,
                    'minLPSVersion' => $package['minLPSVersion'] ?? null,
                    'platforms' => array_map(
                        fn (array $platform): ?string => $platform['displayName']
                            ?? $platform['platformDisplay']
                            ?? null,
                        $package['supportedPlatforms'] ?? [],
                    ),
                ], $plugin['packages'] ?? []),
                'capabilities' => $plugin['capabilities'] ?? [],
                'supportedFeatures' => $plugin['supportedFeatures'] ?? [],
            ];
        });
    }

    private function mapPlugin(array $plugin, array $downloads): array
    {
        $name = $plugin['name'] ?? null;
        $platforms = [];

        foreach ($plugin['packages'] ?? [] as $package) {
            foreach ($package['supportedPlatforms'] ?? [] as $platform) {
                $platformName = $platform['displayName'] ?? $platform['platformDisplay'] ?? null;

                if ($platformName !== null && ! in_array($platformName, $platforms, true)) {
                    $platforms[] = $platformName;
                }
            }
        }

        return [
            'id' => $plugin['id'] ?? null,
            'name' => $name,
            'displayName' => $plugin['displayName'] ?? null,
            'description' => $plugin['description'] ?? null,
            'categories' => array_column($plugin['categories'] ?? [], 'name'),
            'icon' => $plugin['icon'] ?? null,
            'versions' => array_column($plugin['packages'] ?? [], 'version'),
            'platforms' => $platforms,
            'firstPublicAt' => $plugin['firstPublicAt'] ?? null,
            'downloads' => $downloads[$name]['downloads']
                ?? $downloads[$plugin['displayName'] ?? '']['downloads']
                ?? 0,
        ];
    }

    /**
     * Fetch a Next.js data route. The marketplace build ID changes on every
     * Logitech deploy, so a 404 means our cached ID is stale: refresh it and retry once.
     */
    private function getNextData(string $path, array $values = [], array $query = []): array
    {
        $url = fn (): string => $this->marketplaceUrl($path, [$this->buildId(), ...$values]);

        try {
            return $this->getJson($url(), $query);
        } catch (MarketplaceException $exception) {
            $previous = $exception->getPrevious();
            $isNotFound = $previous instanceof RequestException && $previous->response->notFound();

            if (! $isNotFound || filled(config('services.logi_marketplace.build_id'))) {
                throw $exception;
            }

            Cache::forget('marketplace.build_id');

            return $this->getJson($url(), $query);
        }
    }

    private function buildId(): string
    {
        $configured = config('services.logi_marketplace.build_id');

        if (filled($configured)) {
            return $configured;
        }

        return Cache::remember('marketplace.build_id', $this->cacheTtl(), function (): string {
            $html = $this->request($this->marketplaceUrl('/plugins/en/4'))->body();

            if (! preg_match('/"buildId"\s*:\s*"([^"]+)"/', $html, $matches)) {
                throw new MarketplaceException('Could not determine the marketplace build ID.');
            }

            return $matches[1];
        });
    }

    private function getJson(string $url, array $query = []): array
    {
        $data = $this->request($url, $query, acceptJson: true)->json();

        if (! is_array($data)) {
            throw new MarketplaceException('Failed to decode marketplace JSON data.');
        }

        return $data;
    }

    private function request(string $url, array $query = [], bool $acceptJson = false): Response
    {
        try {
            return Http::timeout(15)
                ->when($acceptJson, fn ($request) => $request->acceptJson())
                ->get($url, $query)
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Marketplace request failed.', [
                'url' => $url,
                'status' => $exception instanceof RequestException ? $exception->response->status() : null,
                'error' => $exception->getMessage(),
            ]);

            throw new MarketplaceException('Failed to fetch marketplace data.', previous: $exception);
        }
    }

    private function cacheTtl(): int
    {
        return (int) config('services.logi_marketplace.cache_ttl');
    }

    private function marketplaceUrl(string $path, array $values = []): string
    {
        return config('services.logi_marketplace.base_url').vsprintf($path, $values);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PluginApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_plugins_can_be_filtered_by_platform(): void
    {
        $this->fakeMarketplace([
            [
                'id' => 1,
                'name' => 'WindowsPowerToys',
                'displayName' => 'PowerToys',
                'categories' => [['name' => 'Productivity']],
                'packages' => [[
                    'version' => '1.0.0',
                    'supportedPlatforms' => [['displayName' => 'Windows']],
                ]],
            ],
            [
                'id' => 2,
                'name' => 'MacOnly',
                'displayName' => 'Mac Only',
                'categories' => [],
                'packages' => [[
                    'version' => '1.0.0',
                    'supportedPlatforms' => [['displayName' => 'macOS']],
                ]],
            ],
        ]);

        $this->getJson('/api/v1/plugins?platform=Windows')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'WindowsPowerToys')
            ->assertJsonPath('data.0.platforms.0', 'Windows');
    }

    public function test_plugins_are_paginated(): void
    {
        $this->fakeMarketplace(array_map(fn (int $i): array => [
            'name' => "Plugin{$i}",
            'categories' => [],
            'packages' => [],
        ], range(1, 5)));

        $this->getJson('/api/v1/plugins?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Plugin3')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_per_page_is_limited(): void
    {
        $this->getJson('/api/v1/plugins?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_successful_responses_are_cacheable(): void
    {
        $this->fakeMarketplace([['name' => 'KnownPlugin', 'categories' => [], 'packages' => []]]);

        $response = $this->getJson('/api/v1/plugins')->assertOk();

        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=300', $response->headers->get('Cache-Control'));
        $this->assertNotNull($response->headers->get('ETag'));
    }

    public function test_marketplace_failure_returns_bad_gateway(): void
    {
        Http::fake([
            'marketplace.logi.com/*' => Http::response(null, 500),
        ]);

        $this->getJson('/api/v1/plugins')
            ->assertStatus(502)
            ->assertJsonPath('message', 'Failed to fetch marketplace data.');
    }

    public function test_unknown_plugin_returns_not_found(): void
    {
        $this->fakeMarketplace([['name' => 'KnownPlugin', 'categories' => [], 'packages' => []]]);

        $response = $this->getJson('/api/v1/plugins/UnknownPlugin')
            ->assertNotFound()
            ->assertJsonPath('message', 'Plugin not found.');

        $this->assertStringNotContainsString('public', $response->headers->get('Cache-Control'));
    }

    public function test_plugin_name_lookup_is_case_insensitive(): void
    {
        $this->fakeMarketplace([['name' => 'KnownPlugin', 'categories' => [], 'packages' => []]]);

        Http::fake([
            'marketplace.logi.com/_next/data/currentBuild/plugin/KnownPlugin/en.json*' => Http::response([
                'pageProps' => ['initialState' => ['pluginDetails' => ['data' => ['name' => 'KnownPlugin']]]],
            ]),
        ]);

        $this->getJson('/api/v1/plugins/knownplugin')
            ->assertOk()
            ->assertJsonPath('data.name', 'KnownPlugin');
    }

    public function test_stale_build_id_is_refreshed(): void
    {
        Cache::put('marketplace.build_id', 'staleBuild');

        $this->fakeMarketplace([['name' => 'KnownPlugin', 'categories' => [], 'packages' => []]]);

        $this->getJson('/api/v1/plugins')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'KnownPlugin');

        $this->assertSame('currentBuild', Cache::get('marketplace.build_id'));
    }

    private function fakeMarketplace(array $plugins): void
    {
        Http::fake([
            'marketplace.logi.com/plugins/en/4' => Http::response(
                '<script id="__NEXT_DATA__" type="application/json">{"buildId":"currentBuild"}</script>',
            ),
            'marketplace.logi.com/_next/data/staleBuild/*' => Http::response(null, 404),
            'marketplace.logi.com/_next/data/currentBuild/plugins/en/4.json' => Http::response([
                'pageProps' => [
                    'initialState' => [
                        'pluginList' => [
                            'plugins' => $plugins,
                        ],
                    ],
                ],
            ]),
            'marketplace.logi.com/api/downloads' => Http::response(['plugins' => []]),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\PluginIndexRequest;
use App\Http\Resources\PluginResource;
use App\Services\MarketplacePluginService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class PluginController extends Controller
{
    public function __construct(
        private readonly MarketplacePluginService $plugins,
    ) {}

    public function index(PluginIndexRequest $request): AnonymousResourceCollection
    {
        $plugins = $this->plugins->list($request->filters());
        $page = $request->page();
        $perPage = $request->perPage();

        $paginator = new LengthAwarePaginator(
            array_slice($plugins, ($page - 1) * $perPage, $perPage),
            count($plugins),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return PluginResource::collection($paginator);
    }

    public function show(string $name): PluginResource
    {
        $plugin = $this->plugins->find($name);

        if ($plugin === null) {
            abort(404, 'Plugin not found.');
        }

        return new PluginResource($plugin);
    }
}

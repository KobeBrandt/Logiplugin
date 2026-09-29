<?php

use App\Http\Requests\PluginIndexRequest;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('docs', [
        'apiUrl' => url('/api/v1'),
        'serverCacheMinutes' => intdiv(config('services.logi_marketplace.cache_ttl'), 60),
        'defaultPerPage' => PluginIndexRequest::DEFAULT_PER_PAGE,
        'maxPerPage' => PluginIndexRequest::MAX_PER_PAGE,
    ]);
});

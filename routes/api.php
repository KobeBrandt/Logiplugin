<?php

use App\Http\Controllers\PluginController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('cache.headers:public;max_age=300;etag')
    ->group(function () {
        Route::get('/plugins', [PluginController::class, 'index']);
        Route::get('/plugins/{name}', [PluginController::class, 'show'])->where('name', '[^/]+');
    });

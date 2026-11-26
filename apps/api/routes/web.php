<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Meridian API',
        'status' => 'ok',
        'health' => '/api/v1/health',
        'documentation' => 'See docs/API.md and docs/openapi.yaml in the repository.',
    ]);
});

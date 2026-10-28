<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Pterodactyl\Models\Server;

Route::get('/stats', function () {
    $servers = Server::query()->get(['memory', 'cpu', 'disk']);

    return response()->json([
        'ram' => (float) $servers->sum('memory'),
        'cpu' => (float) $servers->sum('cpu'),
        'disk' => (float) $servers->sum('disk'),
        'servers' => (int) $servers->count(),
    ]);
})->middleware(['auth', AdminAuthenticate::class]);

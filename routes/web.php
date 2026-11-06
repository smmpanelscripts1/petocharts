<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

Route::get('/stats', function () {
    $servers = Server::query()->get(['memory', 'cpu', 'disk']);
    $nodes = Node::query()->get(['memory', 'memory_overallocate', 'disk', 'disk_overallocate']);

    $capacity = static function ($nodes, string $field, string $overField): array {
        $total = 0.0;
        $unlimited = false;
        foreach ($nodes as $node) {
            $over = (int) $node->{$overField};
            if ($over < 0) {
                $unlimited = true;
                continue;
            }
            $total += (float) $node->{$field} * (1 + ($over / 100));
        }

        return ['total' => $total, 'unlimited' => $unlimited];
    };

    $ramCap = $capacity($nodes, 'memory', 'memory_overallocate');
    $diskCap = $capacity($nodes, 'disk', 'disk_overallocate');

    return response()->json([
        'ram' => (float) $servers->sum('memory'),
        'cpu' => (float) $servers->sum('cpu'),
        'disk' => (float) $servers->sum('disk'),
        'servers' => (int) $servers->count(),
        'ram_total' => $ramCap['total'],
        'ram_unlimited' => $ramCap['unlimited'],
        'disk_total' => $diskCap['total'],
        'disk_unlimited' => $diskCap['unlimited'],
    ]);
})->middleware(['auth', AdminAuthenticate::class]);

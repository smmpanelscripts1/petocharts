<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

Route::get('/stats', function () {
    $servers = Server::query()->get(['id', 'uuid', 'status', 'memory', 'cpu', 'disk', 'node_id']);
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

    $repo = app(DaemonServerRepository::class);
    $ramBytes = 0;
    $diskBytes = 0;
    $cpuAbsolute = 0.0;

    foreach ($servers as $server) {
        if (in_array($server->status, ['installing', 'restoring_backup'], true)) {
            continue;
        }

        try {
            $details = $repo->setServer($server)->getDetails();
            $data = is_array($details) ? $details : (array) $details;
            $util = $data['utilization'] ?? [];
            if (is_object($util)) {
                $util = (array) $util;
            }

            $ramBytes += (int) ($util['memory_bytes'] ?? 0);
            $diskBytes += (int) ($util['disk_bytes'] ?? 0);
            $cpuAbsolute += (float) ($util['cpu_absolute'] ?? 0);
        } catch (Throwable $e) {
            // Offline / unreachable nodes contribute 0 live usage.
        }
    }

    $mib = static fn (int $bytes): float => round($bytes / 1024 / 1024, 2);

    return response()->json([
        'ram' => $mib($ramBytes),
        'cpu' => round($cpuAbsolute, 2),
        'disk' => $mib($diskBytes),
        'servers' => (int) $servers->count(),
        'ram_allocated' => (float) $servers->sum('memory'),
        'disk_allocated' => (float) $servers->sum('disk'),
        'cpu_allocated' => (float) $servers->sum('cpu'),
        'ram_total' => $ramCap['total'],
        'ram_unlimited' => $ramCap['unlimited'],
        'disk_total' => $diskCap['total'],
        'disk_unlimited' => $diskCap['unlimited'],
    ]);
})->middleware(['auth', AdminAuthenticate::class]);

Route::get('/servers', function () {
    $servers = Server::query()
        ->with(['allocation:id,ip,port,ip_alias,server_id'])
        ->orderBy('id')
        ->get(['id', 'uuid', 'uuidShort', 'name', 'status', 'memory', 'cpu', 'disk', 'allocation_id', 'node_id']);

    $repo = app(DaemonServerRepository::class);
    $rows = [];

    foreach ($servers as $server) {
        $row = [
            'id' => $server->id,
            'uuidShort' => $server->uuidShort,
            'name' => $server->name,
            'panel_status' => $server->status,
            'limits' => [
                'memory_mib' => (int) $server->memory,
                'cpu' => (float) $server->cpu,
                'disk_mib' => (int) $server->disk,
            ],
            'state' => 'offline',
            'cpu_absolute' => 0.0,
            'memory_bytes' => 0,
            'memory_limit_bytes' => (int) $server->memory * 1024 * 1024,
            'disk_bytes' => 0,
            'network_rx_bytes' => 0,
            'network_tx_bytes' => 0,
            'uptime_ms' => 0,
            'error' => null,
        ];

        if ($server->status === 'installing' || $server->status === 'restoring_backup') {
            $row['state'] = $server->status;
            $rows[] = $row;
            continue;
        }

        try {
            $details = $repo->setServer($server)->getDetails();
            $data = is_array($details) ? $details : (array) $details;
            $util = $data['utilization'] ?? [];
            if (is_object($util)) {
                $util = (array) $util;
            }
            $network = $util['network'] ?? [];
            if (is_object($network)) {
                $network = (array) $network;
            }

            $row['state'] = (string) ($data['state'] ?? $util['state'] ?? 'offline');
            $row['cpu_absolute'] = (float) ($util['cpu_absolute'] ?? 0);
            $row['memory_bytes'] = (int) ($util['memory_bytes'] ?? 0);
            $row['memory_limit_bytes'] = (int) ($util['memory_limit_bytes'] ?? $row['memory_limit_bytes']);
            $row['disk_bytes'] = (int) ($util['disk_bytes'] ?? 0);
            $row['network_rx_bytes'] = (int) ($network['rx_bytes'] ?? 0);
            $row['network_tx_bytes'] = (int) ($network['tx_bytes'] ?? 0);
            $row['uptime_ms'] = (int) ($util['uptime'] ?? 0);
        } catch (Throwable $e) {
            $row['state'] = 'offline';
            $row['error'] = $e->getMessage();
        }

        $rows[] = $row;
    }

    return response()->json(['servers' => $rows, 'ts' => time()]);
})->middleware(['auth', AdminAuthenticate::class]);

Route::post('/power', function (Request $request) {
    $data = $request->validate([
        'action' => 'required|string|in:start,stop,restart,kill',
        'ids' => 'required|array|min:1|max:300',
        'ids.*' => 'integer|exists:servers,id',
    ]);

    @set_time_limit(0);

    $action = $data['action'];
    $servers = Server::query()
        ->whereIn('id', $data['ids'])
        ->get(['id', 'uuid', 'uuidShort', 'name', 'status', 'node_id']);

    $power = app(DaemonPowerRepository::class);
    $ok = [];
    $failed = [];

    foreach ($servers as $server) {
        if (in_array($server->status, ['installing', 'restoring_backup'], true)) {
            $failed[] = [
                'id' => $server->id,
                'uuidShort' => $server->uuidShort,
                'name' => $server->name,
                'error' => 'Server is ' . $server->status,
            ];
            continue;
        }

        try {
            $power->setServer($server)->send($action);
            $ok[] = [
                'id' => $server->id,
                'uuidShort' => $server->uuidShort,
                'name' => $server->name,
            ];
        } catch (Throwable $e) {
            $failed[] = [
                'id' => $server->id,
                'uuidShort' => $server->uuidShort,
                'name' => $server->name,
                'error' => $e->getMessage(),
            ];
        }
    }

    return response()->json([
        'action' => $action,
        'ok' => $ok,
        'failed' => $failed,
        'ok_count' => count($ok),
        'failed_count' => count($failed),
    ]);
})->middleware(['auth', AdminAuthenticate::class]);

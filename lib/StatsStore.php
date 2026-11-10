<?php

namespace PetoCharts;

class StatsStore
{
    public const KEEP_SECONDS = 7 * 86400;
    public const MIN_INTERVAL = 60;
    public const MAX_POINTS = 420;

    public static function path(): string
    {
        $env = trim((string) env('PETOCHARTS_HISTORY', getenv('PETOCHARTS_HISTORY') ?: ''));
        if ($env !== '') {
            return $env;
        }

        $candidates = [
            '/app/var/petocharts/samples.jsonl',
            function_exists('storage_path') ? storage_path('app/petocharts/samples.jsonl') : '',
            function_exists('storage_path') ? storage_path('.extensions/petocharts/samples.jsonl') : '',
            sys_get_temp_dir() . '/petocharts-samples.jsonl',
        ];

        foreach ($candidates as $path) {
            if ($path === '') {
                continue;
            }
            $dir = dirname($path);
            if (is_dir($dir) || @mkdir($dir, 0775, true)) {
                return $path;
            }
        }

        return sys_get_temp_dir() . '/petocharts-samples.jsonl';
    }

    public static function record(array $stats): void
    {
        $now = time();
        $bucket = (int) (floor($now / self::MIN_INTERVAL) * self::MIN_INTERVAL);
        $path = self::path();
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return;
        }

        $fh = @fopen($path, 'c+');
        if ($fh === false) {
            return;
        }

        try {
            if (!flock($fh, LOCK_EX)) {
                return;
            }

            $last = 0;
            $info = fstat($fh);
            $size = (int) ($info['size'] ?? 0);
            if ($size > 2) {
                fseek($fh, -min(800, $size), SEEK_END);
                $chunk = (string) stream_get_contents($fh);
                $lines = preg_split("/\r\n|\n|\r/", trim($chunk)) ?: [];
                $tail = json_decode((string) end($lines), true);
                $last = (int) ($tail['t'] ?? 0);
            }
            if ($last >= $bucket) {
                return;
            }

            fseek($fh, 0, SEEK_END);
            $row = [
                't' => $bucket,
                'ram' => round((float) ($stats['ram'] ?? 0), 2),
                'cpu' => round((float) ($stats['cpu'] ?? 0), 2),
                'disk' => round((float) ($stats['disk'] ?? 0), 2),
                'servers' => (int) ($stats['servers'] ?? 0),
                'ram_a' => round((float) ($stats['ram_allocated'] ?? 0), 2),
                'disk_a' => round((float) ($stats['disk_allocated'] ?? 0), 2),
                'cpu_a' => round((float) ($stats['cpu_allocated'] ?? 0), 2),
            ];
            fwrite($fh, json_encode($row, JSON_UNESCAPED_SLASHES) . "\n");
            fflush($fh);

            $size = (int) @filesize($path);
            if ($size > 1500000 || ($bucket % 3600 === 0)) {
                flock($fh, LOCK_UN);
                fclose($fh);
                $fh = null;
                self::prune($path);
            }
        } finally {
            if (is_resource($fh)) {
                flock($fh, LOCK_UN);
                fclose($fh);
            }
        }
    }

    public static function history(string $range = '7d'): array
    {
        $seconds = $range === '24h' ? 86400 : self::KEEP_SECONDS;
        $from = time() - $seconds;
        $rows = [];
        foreach (self::readAll() as $row) {
            if ((int) ($row['t'] ?? 0) >= $from) {
                $rows[] = $row;
            }
        }

        $series = self::downsample($rows, self::MAX_POINTS);

        return [
            'range' => $range === '24h' ? '24h' : '7d',
            'from' => $from,
            'to' => time(),
            'count' => count($rows),
            'kept_days' => 7,
            'interval_seconds' => self::MIN_INTERVAL,
            'path' => self::path(),
            'series' => $series,
            'summary' => [
                'ram' => self::metricSummary($rows, 'ram'),
                'cpu' => self::metricSummary($rows, 'cpu'),
                'disk' => self::metricSummary($rows, 'disk'),
                'servers' => self::metricSummary($rows, 'servers'),
            ],
            'daily' => self::daily($rows),
        ];
    }

    private static function readAll(): array
    {
        $path = self::path();
        if (!is_file($path)) {
            return [];
        }

        $out = [];
        $fh = @fopen($path, 'r');
        if ($fh === false) {
            return [];
        }
        try {
            flock($fh, LOCK_SH);
            while (($line = fgets($fh)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $row = json_decode($line, true);
                if (is_array($row) && !empty($row['t'])) {
                    $out[] = $row;
                }
            }
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }

        return $out;
    }

    private static function prune(string $path): void
    {
        $cutoff = time() - self::KEEP_SECONDS;
        $keep = [];
        foreach (self::readAll() as $row) {
            if ((int) $row['t'] >= $cutoff) {
                $keep[] = json_encode($row, JSON_UNESCAPED_SLASHES);
            }
        }
        $tmp = $path . '.tmp';
        file_put_contents($tmp, $keep === [] ? '' : implode("\n", $keep) . "\n", LOCK_EX);
        @rename($tmp, $path);
    }

    private static function downsample(array $rows, int $max): array
    {
        $n = count($rows);
        if ($n <= $max) {
            return $rows;
        }
        $out = [];
        $step = $n / $max;
        for ($i = 0; $i < $max; $i++) {
            $out[] = $rows[(int) floor($i * $step)];
        }
        $out[] = $rows[$n - 1];

        return $out;
    }

    private static function metricSummary(array $rows, string $field): array
    {
        if ($rows === []) {
            return ['avg' => 0, 'min' => 0, 'max' => 0, 'peak_at' => null];
        }
        $min = null;
        $max = null;
        $sum = 0.0;
        $peakAt = null;
        foreach ($rows as $row) {
            $value = (float) ($row[$field] ?? 0);
            $sum += $value;
            if ($min === null || $value < $min) {
                $min = $value;
            }
            if ($max === null || $value > $max) {
                $max = $value;
                $peakAt = (int) $row['t'];
            }
        }

        return [
            'avg' => round($sum / count($rows), 2),
            'min' => round((float) $min, 2),
            'max' => round((float) $max, 2),
            'peak_at' => $peakAt,
        ];
    }

    private static function daily(array $rows): array
    {
        $tz = function_exists('config') ? (string) config('app.timezone', 'UTC') : 'UTC';
        try {
            $zone = new \DateTimeZone($tz);
        } catch (\Throwable $e) {
            $zone = new \DateTimeZone('UTC');
        }

        $groups = [];
        foreach ($rows as $row) {
            $day = (new \DateTimeImmutable('@' . (int) $row['t']))->setTimezone($zone)->format('Y-m-d');
            if (!isset($groups[$day])) {
                $groups[$day] = ['day' => $day, 'n' => 0, 'ram' => 0.0, 'cpu' => 0.0, 'disk' => 0.0, 'ram_max' => 0.0, 'cpu_max' => 0.0, 'disk_max' => 0.0];
            }
            $groups[$day]['n']++;
            $groups[$day]['ram'] += (float) ($row['ram'] ?? 0);
            $groups[$day]['cpu'] += (float) ($row['cpu'] ?? 0);
            $groups[$day]['disk'] += (float) ($row['disk'] ?? 0);
            $groups[$day]['ram_max'] = max($groups[$day]['ram_max'], (float) ($row['ram'] ?? 0));
            $groups[$day]['cpu_max'] = max($groups[$day]['cpu_max'], (float) ($row['cpu'] ?? 0));
            $groups[$day]['disk_max'] = max($groups[$day]['disk_max'], (float) ($row['disk'] ?? 0));
        }

        $out = [];
        foreach ($groups as $group) {
            $n = max(1, $group['n']);
            $out[] = [
                'day' => $group['day'],
                'samples' => $group['n'],
                'ram_avg' => round($group['ram'] / $n, 2),
                'ram_max' => round($group['ram_max'], 2),
                'cpu_avg' => round($group['cpu'] / $n, 2),
                'cpu_max' => round($group['cpu_max'], 2),
                'disk_avg' => round($group['disk'] / $n, 2),
                'disk_max' => round($group['disk_max'], 2),
            ];
        }

        return $out;
    }
}

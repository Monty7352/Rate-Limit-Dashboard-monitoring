<?php

namespace Dev\RateLimitDashboard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackRateLimits
{
    public function handle(Request $request, Closure $next): Response
    {
        $dashboardPath = config('rate-limit-dashboard.path', 'rate-limit-dashboard');
        if ($request->is($dashboardPath . '*')) {
            return $next($request);
        }

        $mainIp = $request->ip();
        $systemIp = $this->extractSystemIp($request, $mainIp);

        // 1. Check if IP is Blacklisted / Blocked
        $blacklistedIps = Cache::get('rld_blacklisted_ips', []);
        if (in_array($systemIp, $blacklistedIps) || in_array($mainIp, $blacklistedIps)) {
            return response()->json([
                'error' => 'Your IP address has been automatically blocked due to suspicious activity.',
                'system_ip' => $systemIp,
            ], 403);
        }

        $response = $next($request);

        // 2. Track Request Metrics
        $path = '/' . ltrim($request->path(), '/');
        $type = $request->is('api/*') ? 'API' : 'WEB';
        $statusCode = $response->getStatusCode();

        $key = "rld_metrics:" . md5($mainIp . '_' . $systemIp . '_' . $path);
        $ttl = config('rate-limit-dashboard.cache_ttl', 86400);

        $metric = Cache::get($key, [
            'main_ip' => $mainIp,
            'system_ip' => $systemIp,
            'path' => $path,
            'type' => $type,
            'hits' => 0,
            'rate_limited_count' => 0,
            'last_status' => $statusCode,
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $metric['hits']++;
        $metric['last_status'] = $statusCode;
        $metric['updated_at'] = now()->format('Y-m-d H:i:s');

        if ($statusCode === 429) {
            $metric['rate_limited_count']++;
        }

        Cache::put($key, $metric, $ttl);

        $index = Cache::get('rld_master_index', []);
        if (!in_array($key, $index)) {
            $index[] = $key;
            Cache::put('rld_master_index', $index, $ttl);
        }

        // 3. Auto-Block Logic Check
        if (config('rate-limit-dashboard.auto_block.enabled', true)) {
            $maxHits = config('rate-limit-dashboard.auto_block.max_hits_per_minute', 50);
            
            // Per-minute IP counter check
            $minuteKey = "rld_minute_hits:" . $systemIp . ":" . now()->format('YmdHi');
            $minuteHits = Cache::increment($minuteKey);
            Cache::put($minuteKey, $minuteHits, 120); // Keep for 2 mins

            if ($minuteHits > $maxHits && !in_array($systemIp, $blacklistedIps)) {
                $blacklistedIps[] = $systemIp;
                Cache::put('rld_blacklisted_ips', $blacklistedIps);
            }
        }

        return $response;
    }

    private function extractSystemIp(Request $request, string $defaultIp): string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if ($request->server($header)) {
                $ips = explode(',', $request->server($header));
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if ($ip !== $defaultIp && filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return $defaultIp;
    }
}
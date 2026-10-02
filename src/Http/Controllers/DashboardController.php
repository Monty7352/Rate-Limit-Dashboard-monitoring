<?php

namespace Dev\RateLimitDashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $masterKeys = Cache::get('rld_master_index', []);
        $blacklistedIps = Cache::get('rld_blacklisted_ips', []);
        $metrics = [];

        $totalHits = 0;
        $totalBlocked = 0;

        foreach ($masterKeys as $key) {
            if ($data = Cache::get($key)) {
                // Safe fallback logic purane cache data ke liye
                $systemIp = $data['system_ip'] ?? $data['ip'] ?? '127.0.0.1';
                $mainIp = $data['main_ip'] ?? $data['ip'] ?? '127.0.0.1';

                $data['system_ip'] = $systemIp;
                $data['main_ip'] = $mainIp;
                $data['is_blocked'] = in_array($systemIp, $blacklistedIps) || in_array($mainIp, $blacklistedIps);

                $metrics[] = $data;
                $totalHits += $data['hits'] ?? 0;
                $totalBlocked += $data['rate_limited_count'] ?? 0;
            }
        }

        usort($metrics, fn($a, $b) => ($b['hits'] ?? 0) <=> ($a['hits'] ?? 0));

        $stats = [
            'total_tracked_routes' => count($metrics),
            'total_hits' => $totalHits,
            'total_blocked' => $totalBlocked,
            'total_blacklisted' => count($blacklistedIps),
        ];

        return view('rate-limit-dashboard::dashboard', compact('metrics', 'stats', 'blacklistedIps'));
    }
    public function toggleBlock(Request $request)
    {
        $ip = $request->input('ip');
        $blacklistedIps = Cache::get('rld_blacklisted_ips', []);

        if (in_array($ip, $blacklistedIps)) {
            $blacklistedIps = array_diff($blacklistedIps, [$ip]);
            $msg = "IP {$ip} has been unblocked successfully!";
        } else {
            $blacklistedIps[] = $ip;
            $msg = "IP {$ip} has been blocked successfully!";
        }

        Cache::put('rld_blacklisted_ips', array_values($blacklistedIps));

        return redirect()->back()->with('status', $msg);
    }

    public function clear()
    {
        $masterKeys = Cache::get('rld_master_index', []);
        foreach ($masterKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget('rld_master_index');

        return redirect()->back()->with('status', 'Metrics cleared successfully!');
    }
}

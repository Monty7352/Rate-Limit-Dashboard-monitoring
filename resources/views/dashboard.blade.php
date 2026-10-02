<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Meta Tag Method (Simple & Zero Config) -->
    <meta http-equiv="refresh" content="5">
    <title>Rate Limit & Traffic Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6 font-sans">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-3xl font-extrabold text-indigo-400 tracking-tight">⚡ Rate Limit & Traffic Monitor</h1>
                <p class="text-slate-400 text-sm mt-1">High-Performance In-Memory Analytics (Web & API)</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url()->current() }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-4 py-2 rounded-lg text-sm transition">
                    🔄 Refresh
                </a>
                <form action="{{ route('rate-limit-dashboard.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 font-medium px-4 py-2 rounded-lg text-sm transition">
                        🗑️ Reset Stats
                    </button>
                </form>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-xl">
                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Active Endpoints</p>
                <h3 class="text-3xl font-bold text-white mt-2">{{ $stats['total_tracked_routes'] }}</h3>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-xl">
                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Total Hits</p>
                <h3 class="text-3xl font-bold text-emerald-400 mt-2">{{ $stats['total_hits'] }}</h3>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-xl">
                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Blocked (429)</p>
                <h3 class="text-3xl font-bold text-rose-400 mt-2">{{ $stats['total_blocked'] }}</h3>
            </div>
        </div>

        <!-- Traffic Breakdown Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
            <div class="p-4 border-b border-slate-800">
                <h2 class="font-semibold text-lg text-slate-200">Traffic Breakdown</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-950/50 text-slate-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="p-4">Type</th>
                            <th class="p-4">Main IP (Public)</th>
                            <th class="p-4">System IP (Local)</th>
                            <th class="p-4">Route Path</th>
                            <th class="p-4">Hits</th>
                            <th class="p-4">Blocked (429)</th>
                            <th class="p-4">Last Status</th>
                            <th class="p-4">Last Hit Time</th>
                            <th class="p-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-sm">
                        @forelse($metrics as $m)
                            <tr class="hover:bg-slate-800/50 transition">
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $m['type'] === 'API' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-blue-500/20 text-blue-300 border border-blue-500/30' }}">
                                        {{ $m['type'] }}
                                    </span>
                                </td>
                                <td class="p-4 font-mono text-slate-300">{{ $m['main_ip'] ?? '127.0.0.1' }}</td>
                                <td class="p-4 font-mono text-indigo-300 font-bold">{{ $m['system_ip'] ?? '127.0.0.1' }}</td>
                                <td class="p-4 font-mono text-indigo-300">{{ $m['path'] }}</td>
                                <td class="p-4 font-bold text-emerald-400">{{ $m['hits'] }}</td>
                                <td class="p-4 font-bold {{ $m['rate_limited_count'] > 0 ? 'text-rose-400' : 'text-slate-500' }}">
                                    {{ $m['rate_limited_count'] }}
                                </td>
                                <td class="p-4">
                                    @if($m['last_status'] >= 400)
                                        <span class="px-2 py-0.5 rounded text-xs bg-rose-500/20 text-rose-300 font-mono">{{ $m['last_status'] }}</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-xs bg-emerald-500/20 text-emerald-300 font-mono">{{ $m['last_status'] }}</span>
                                    @endif
                                </td>
                                <td class="p-4 text-xs text-slate-400">{{ $m['updated_at'] }}</td>
                                <td class="p-4">
                                    <form action="{{ route('rate-limit-dashboard.toggle-block') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="ip" value="{{ $m['system_ip'] }}">
                                        @if(!empty($m['is_blocked']))
                                            <button type="submit" class="bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 px-3 py-1 rounded text-xs font-semibold transition">
                                                ✅ Unblock
                                            </button>
                                        @else
                                            <button type="submit" class="bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 px-3 py-1 rounded text-xs font-semibold transition">
                                                🚫 Block IP
                                            </button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-500">
                                    No traffic recorded yet. Make some requests to Web or API routes!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Har 5 second (5000ms) me page reload karega
        setInterval(function() {
            window.location.reload();
        }, 5000);
    });
</script>
</body>
</html>
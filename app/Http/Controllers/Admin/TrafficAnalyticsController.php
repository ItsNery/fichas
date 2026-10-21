<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrafficRequestEvent;
use Illuminate\Http\Request;

class TrafficAnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:trafico.ver');
    }

    public function index(Request $request)
    {
        $hours = (int) $request->query('hours', 24);
        $hours = in_array($hours, [1, 6, 24, 72, 168], true) ? $hours : 24;
        $from = now()->subHours($hours);
        $events = TrafficRequestEvent::query()->where('occurred_at', '>=', $from);

        $summary = [
            'requests' => (clone $events)->count(),
            'uniqueIps' => (clone $events)->whereNotNull('client_ip')->distinct('client_ip')->count('client_ip'),
            'rateLimited' => (clone $events)->where('status', 429)->count(),
            'serverErrors' => (clone $events)->where('status', '>=', 500)->count(),
            'averageDuration' => (int) round((clone $events)->avg('duration_ms') ?? 0),
        ];

        $topRoutes = (clone $events)
            ->selectRaw('route_name, COUNT(*) as requests, AVG(duration_ms) as average_duration, SUM(CASE WHEN status = 429 THEN 1 ELSE 0 END) as rate_limited')
            ->groupBy('route_name')
            ->orderByDesc('requests')
            ->limit(10)
            ->get();

        $topIps = (clone $events)
            ->whereNotNull('client_ip')
            ->selectRaw('client_ip, COUNT(*) as requests, AVG(duration_ms) as average_duration, SUM(CASE WHEN status = 429 THEN 1 ELSE 0 END) as rate_limited, MAX(occurred_at) as last_seen')
            ->groupBy('client_ip')
            ->orderByDesc('requests')
            ->limit(25)
            ->get();

        $canViewIps = $request->user()->can('trafico.ip.ver');
        $topIps->each(function ($source) use ($canViewIps) {
            $source->display_ip = $canViewIps ? $source->client_ip : $this->maskIp($source->client_ip);
        });

        $recentEvents = (clone $events)->latest('occurred_at')->paginate(25)->withQueryString();

        return view('admin.trafico.index', compact('hours', 'summary', 'topRoutes', 'topIps', 'recentEvents'));
    }

    private function maskIp(?string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return explode('.', $ip)[0].'.***.***.***';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return implode(':', array_slice(explode(':', $ip), 0, 2)).':****:****:****:****:****:****';
        }

        return 'No disponible';
    }
}

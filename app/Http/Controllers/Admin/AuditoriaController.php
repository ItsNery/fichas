<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditoriaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:auditoria.ver');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $logs = Activity::with('causer', 'subject')
            ->when($request->modelo, fn($q, $m) => $q->where('subject_type', $m))
            ->when($request->usuario, fn($q, $u) => $q->where('causer_id', $u))
            ->when($request->desde, fn($q, $d) => $q->where('created_at', '>=', $d))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.auditoria.index', compact('logs', 'perPage'));
    }
}

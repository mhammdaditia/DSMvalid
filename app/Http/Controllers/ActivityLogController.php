<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        // ✅ VALIDASI Input
        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'action' => 'nullable|string|in:login,logout,page_visit',
            'date' => 'nullable|date',
        ]);

        $query = DB::table('activity_logs')
            ->join('users', 'activity_logs.user_id', '=', 'users.id')
            ->select(
                'activity_logs.id',
                'activity_logs.user_id',
                'users.name as user_name',
                'users.username',
                'activity_logs.route',
                'activity_logs.url',
                'activity_logs.method',
                'activity_logs.action',
                'activity_logs.ip_address',
                'activity_logs.hostname',
                'activity_logs.created_at'
            );

        if ($request->filled('user_id')) {
            $query->where('activity_logs.user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('activity_logs.action', $request->action);
        }

        if ($request->filled('date')) {
            $query->whereDate('activity_logs.created_at', $request->date);
        } else {
            $query->where('activity_logs.created_at', '>=', Carbon::now()->subDays(30));
        }

        $logs = $query->orderBy('activity_logs.created_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        // ✅ FIX: Remove email_verified_at filter
        $users = Cache::remember('activity_logs_users_list', 300, function() {
            return DB::table('users')
                ->select('id', 'name', 'username')
                ->orderBy('name')
                ->get();
        });

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'users' => $users,
            'filters' => [
                'user_id' => $request->user_id,
                'action' => $request->action,
                'date' => $request->date,
            ],
        ]);
    }
}
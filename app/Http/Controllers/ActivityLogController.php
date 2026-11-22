<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogIndexRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(ActivityLogIndexRequest $request): View
    {
        $filters = $request->validated();
        $logs = ActivityLog::query()
            ->with('user:id,name')
            ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['tanggal_akhir'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['aksi'] ?? null, fn ($query, $action) => $query->where('aksi', $action))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();
        $users = User::query()->orderBy('name')->get(['id', 'name']);
        $actions = ActivityLog::query()
            ->select('aksi')
            ->distinct()
            ->orderBy('aksi')
            ->pluck('aksi');

        return view('activity-logs.index', compact('logs', 'users', 'actions', 'filters'));
    }
}

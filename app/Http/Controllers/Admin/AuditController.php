<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ActivityLog::query()
            ->with('actor:id,username,nickname')
            ->orderBy('created_at', 'desc');

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->whereHas('actor', function ($actorQuery) use ($search): void {
                    $actorQuery->where('username', 'like', "%{$search}%")
                        ->orWhere('nickname', 'like', "%{$search}%");
                })
                    ->orWhere('target_username', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Admin/Audit', [
            'logs' => $query->paginate(50, ['id', 'actor_id', 'event', 'target_username', 'ip_address', 'created_at']),
            'filters' => $request->only('event', 'search'),
        ]);
    }
}

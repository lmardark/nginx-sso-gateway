<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

final class AuditController extends Controller
{
    public function index(): Response
    {
        $logs = ActivityLog::query()
            ->with('actor:id,username,nickname')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get(['id', 'actor_id', 'event', 'target_username', 'ip_address', 'created_at']);

        return Inertia::render('Admin/Audit', ['logs' => $logs]);
    }
}

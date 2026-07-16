<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuditController extends Controller
{
    public function index(Request $request): Response
    {
        $action = (string) $request->query('action', '');
        $search = trim((string) $request->query('search', ''));

        $logs = AuditLog::query()
            ->when(
                AuditAction::tryFrom($action) !== null,
                fn ($query) => $query->where('action', $action),
            )
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('actor_label', 'like', "%{$search}%")
                ->orWhere('target_label', 'like', "%{$search}%")))
            ->latest()
            ->paginate(50)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'actor' => $log->actor_label,
                'actorId' => $log->actor_id,
                'action' => $log->action,
                'targetType' => $log->target_type,
                'targetLabel' => $log->target_label,
                'metadata' => $log->metadata,
                'ipAddress' => $log->ip_address,
                'createdAt' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/Audit', [
            'logs' => $logs,
            'filters' => ['action' => $action, 'search' => $search],
            'actions' => array_map(fn (AuditAction $case) => $case->value, AuditAction::cases()),
        ]);
    }
}

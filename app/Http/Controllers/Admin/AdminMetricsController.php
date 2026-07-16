<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Board;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminMetricsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Metrics', [
            'totals' => [
                'users' => User::query()->count(),
                'admins' => User::query()->where('role', UserRole::Admin)->count(),
                'suspended' => User::query()->whereNotNull('suspended_at')->count(),
                'boards' => Board::query()->count(),
                'tasks' => Task::query()->count(),
                'openTasks' => Task::query()->where('completed', false)->count(),
                'notes' => Note::query()->count(),
            ],
            'signupsLast30Days' => $this->dailyCounts('users'),
            'boardsLast30Days' => $this->dailyCounts('boards'),
            'recentActivity' => AuditLog::query()
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'id' => $log->id,
                    'actor' => $log->actor_label,
                    'action' => $log->action,
                    'target' => $log->target_label,
                    'createdAt' => $log->created_at?->toIso8601String(),
                ])
                ->all(),
        ]);
    }

    /**
     * A dense 30-day series — days with no rows still get a zero, so the sparkline
     * doesn't silently compress gaps into slopes. Grouped in SQL rather than by
     * hydrating a month of rows just to count them.
     *
     * @return array<int, array{date: string, count: int}>
     */
    private function dailyCounts(string $table): array
    {
        $since = Carbon::today()->subDays(29);

        $counts = DB::table($table)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, 29))
            ->map(function (int $offset) use ($since, $counts): array {
                $date = $since->copy()->addDays($offset)->toDateString();

                return ['date' => $date, 'count' => (int) ($counts[$date] ?? 0)];
            })
            ->all();
    }
}

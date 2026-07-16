<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminOversightTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_any_board_through_the_oversight_area()
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Private Roadmap']);
        $board->tasks()->create(['title' => 'Ship it', 'position' => 0]);

        $this->actingAs($admin)
            ->get(route('admin.boards.show', $board))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/BoardDetail')
                ->where('board.name', 'Private Roadmap')
                ->where('board.owner.email', $owner->email)
                ->has('board.tasks', 1));
    }

    /**
     * Oversight is deliberately out-of-band: it must not widen the board Gate, because
     * routes/channels.php authorises the presence channel with that same `view` check.
     */
    public function test_oversight_does_not_grant_the_admin_board_gate_access()
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Private Roadmap']);

        $this->assertTrue(Gate::forUser($admin)->denies('view', $board));
        $this->assertTrue(Gate::forUser($admin)->denies('update', $board));

        $this->actingAs($admin)->get(route('boards.show', $board))->assertNotFound();
    }

    public function test_admin_board_views_are_audited()
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Private Roadmap']);

        $this->actingAs($admin)->get(route('admin.boards.show', $board));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'board.viewed_by_admin',
            'actor_id' => $admin->id,
            'target_label' => 'Private Roadmap',
        ]);
    }

    public function test_admin_can_list_and_search_every_board()
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $owner->boards()->create(['name' => 'Findable Board']);
        $owner->boards()->create(['name' => 'Other Board']);

        $this->actingAs($admin)
            ->get(route('admin.boards.index'))
            ->assertInertia(fn (Assert $page) => $page->has('boards.data', 2));

        $this->actingAs($admin)
            ->get(route('admin.boards.index', ['search' => 'Findable']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('boards.data', 1)
                ->where('boards.data.0.name', 'Findable Board'));
    }

    public function test_admin_can_view_metrics()
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->tasks()->create(['title' => 'Open task', 'position' => 0]);

        $this->actingAs($admin)
            ->get(route('admin.metrics'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Metrics')
                ->where('totals.users', 2)
                ->where('totals.admins', 1)
                ->where('totals.boards', 1)
                ->where('totals.openTasks', 1)
                ->has('signupsLast30Days', 30)
                ->has('boardsLast30Days', 30));
    }

    public function test_admin_can_view_and_filter_the_audit_log()
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.users.suspension', $other), ['suspended' => true]);
        $this->actingAs($admin)->patch(route('admin.users.role', $other), ['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.audit.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Audit')
                ->has('logs.data', 2));

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['action' => 'user.suspended']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'user.suspended'));
    }

    /**
     * The whole point of the trail: deleting the admin who acted must not erase the
     * record of what they did. This is why actor_id is nullOnDelete rather than the
     * cascadeOnDelete used everywhere else in the schema.
     */
    public function test_audit_entries_survive_deletion_of_their_actor()
    {
        $admin = User::factory()->admin()->create();
        $keeper = User::factory()->admin()->create();
        $victim = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.users.suspension', $victim), ['suspended' => true]);

        $this->actingAs($keeper)->delete(route('admin.users.destroy', $admin));

        $log = AuditLog::query()->where('action', 'user.suspended')->firstOrFail();

        $this->assertNull($log->actor_id);
        $this->assertSame($admin->email, $log->actor_label);
    }
}

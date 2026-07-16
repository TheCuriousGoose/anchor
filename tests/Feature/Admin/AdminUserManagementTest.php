<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_user_list()
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Regular Person']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Users')
                ->has('users.data', 2));
    }

    public function test_admin_can_search_users_by_name_or_email()
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Findable', 'email' => 'findable@example.com']);
        User::factory()->create(['name' => 'Hidden', 'email' => 'hidden@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'findable']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Findable'));
    }

    public function test_non_admin_users_cannot_reach_the_admin_area()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.users.index'))->assertNotFound();
        $this->actingAs($user)->get(route('admin.metrics'))->assertNotFound();
        $this->actingAs($user)->get(route('admin.audit.index'))->assertNotFound();
        $this->actingAs($user)->get(route('admin.boards.index'))->assertNotFound();
    }

    public function test_guests_are_redirected_from_the_admin_area()
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_promote_and_demote_a_user()
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $other), ['role' => 'admin'])
            ->assertRedirect();

        $this->assertSame(UserRole::Admin, $other->fresh()->role);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.role_changed',
            'actor_id' => $admin->id,
            'target_id' => (string) $other->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $other), ['role' => 'user'])
            ->assertRedirect();

        $this->assertSame(UserRole::User, $other->fresh()->role);
    }

    public function test_admin_cannot_change_their_own_role()
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $admin), ['role' => 'user'])
            ->assertStatus(422);

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_the_last_active_admin_cannot_be_demoted()
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        // Demoting `other` would leave `admin` — allowed.
        $this->actingAs($admin)
            ->patch(route('admin.users.role', $other), ['role' => 'user'])
            ->assertRedirect();

        // `admin` is now the only one left, and cannot be demoted by anyone.
        $promoted = User::factory()->admin()->create();
        $this->actingAs($promoted)
            ->patch(route('admin.users.role', $promoted), ['role' => 'user'])
            ->assertStatus(422);
    }

    public function test_admin_can_suspend_and_unsuspend_a_user()
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.suspension', $other), ['suspended' => true])
            ->assertRedirect();

        $this->assertTrue($other->fresh()->isSuspended());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspended']);

        $this->actingAs($admin)
            ->patch(route('admin.users.suspension', $other), ['suspended' => false])
            ->assertRedirect();

        $this->assertFalse($other->fresh()->isSuspended());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.unsuspended']);
    }

    public function test_admin_cannot_suspend_their_own_account()
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.suspension', $admin), ['suspended' => true])
            ->assertStatus(422);

        $this->assertFalse($admin->fresh()->isSuspended());
    }

    public function test_the_last_active_admin_cannot_be_suspended()
    {
        $admin = User::factory()->admin()->create();
        $solo = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.suspension', $solo), ['suspended' => true])
            ->assertRedirect();

        // Only `admin` remains active; a second admin may not be suspended away.
        $this->actingAs($admin)
            ->patch(route('admin.users.suspension', $admin), ['suspended' => true])
            ->assertStatus(422);
    }

    public function test_admin_can_delete_a_user()
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $other))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.deleted',
            'target_label' => $other->email,
        ]);
    }

    public function test_admin_cannot_delete_their_own_account_from_the_admin_area()
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_deleting_a_user_records_the_boards_it_destroys()
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();

        $board = $owner->boards()->create(['name' => 'Shared Roadmap']);
        $board->collaborators()->attach($collaborator->id, ['role' => 'editor']);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $owner));

        $log = AuditLog::query()->where('action', 'user.deleted')->firstOrFail();

        $this->assertSame(['Shared Roadmap'], $log->metadata['boards_deleted']);
        $this->assertSame(1, $log->metadata['collaborators_affected']);
        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    }
}

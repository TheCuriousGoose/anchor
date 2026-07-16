<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_deletion_is_recorded()
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($collaborator->id, ['role' => 'editor']);

        $this->actingAs($owner)
            ->deleteJson(route('boards.destroy', $board))
            ->assertNoContent();

        $log = AuditLog::query()->where('action', 'board.deleted')->firstOrFail();

        $this->assertSame($owner->id, $log->actor_id);
        $this->assertSame('Roadmap', $log->target_label);
        $this->assertSame(1, $log->metadata['collaborators_affected']);
    }

    public function test_revoking_a_board_share_is_recorded()
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($collaborator->id, ['role' => 'editor']);

        $this->actingAs($owner)
            ->deleteJson(route('boards.share.destroy', [$board, $collaborator]))
            ->assertNoContent();

        $log = AuditLog::query()->where('action', 'board.share_revoked')->firstOrFail();

        $this->assertSame($owner->id, $log->actor_id);
        $this->assertSame($collaborator->email, $log->metadata['collaborator_email']);
    }

    public function test_self_account_deletion_is_recorded()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $log = AuditLog::query()->where('action', 'account.self_deleted')->firstOrFail();

        // The actor row is gone, but the trail still names who it was.
        $this->assertNull($log->actor_id);
        $this->assertSame($user->email, $log->actor_label);
    }

    public function test_the_promote_command_creates_the_first_admin()
    {
        $user = User::factory()->create();

        $this->artisan('admin:promote', ['email' => $user->email])
            ->expectsOutputToContain('is now admin')
            ->assertExitCode(0);

        $this->assertSame(UserRole::Admin, $user->fresh()->role);

        // No authenticated actor on the console path.
        $log = AuditLog::query()->where('action', 'user.role_changed')->firstOrFail();
        $this->assertNull($log->actor_id);
        $this->assertSame('system', $log->actor_label);
        $this->assertSame('console', $log->metadata['via']);
    }

    public function test_the_promote_command_can_demote_and_reports_unknown_emails()
    {
        $admin = User::factory()->admin()->create();

        $this->artisan('admin:promote', ['email' => $admin->email, '--demote' => true])
            ->assertExitCode(0);

        $this->assertSame(UserRole::User, $admin->fresh()->role);

        $this->artisan('admin:promote', ['email' => 'nobody@example.com'])
            ->assertExitCode(1);
    }
}

<?php

namespace Tests\Feature;

use App\Models\BoardInvitation;
use App\Models\User;
use App\Notifications\BoardInvitationSent;
use App\Notifications\BoardShared;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BoardInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sharing_with_an_unknown_email_creates_an_invitation_and_sends_a_mail()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('boards.share.store', $board), [
                'email' => 'newcomer@example.com',
                'role' => 'editor',
            ])
            ->assertCreated()
            ->assertJsonPath('email', 'newcomer@example.com')
            ->assertJsonPath('pending', true);

        $this->assertDatabaseHas('board_invitations', [
            'board_id' => $board->id,
            'email' => 'newcomer@example.com',
            'role' => 'editor',
            'invited_by' => $owner->id,
        ]);

        Notification::assertSentOnDemand(BoardInvitationSent::class);
    }

    public function test_registering_with_an_invited_email_grants_the_board()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)->postJson(route('boards.share.store', $board), [
            'email' => 'newcomer@example.com',
            'role' => 'viewer',
        ]);

        // Fortify's register route is guest-only; staying logged in as the owner would
        // silently redirect instead of registering.
        $this->post(route('logout'));

        $this->post(route('register'), [
            'name' => 'Newcomer',
            'email' => 'newcomer@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect();

        $newcomer = User::where('email', 'newcomer@example.com')->firstOrFail();

        $this->assertDatabaseHas('board_user', [
            'board_id' => $board->id,
            'user_id' => $newcomer->id,
            'role' => 'viewer',
        ]);

        // The invitation is consumed, not left lying around.
        $this->assertDatabaseMissing('board_invitations', ['email' => 'newcomer@example.com']);
    }

    public function test_an_expired_invitation_is_not_redeemed()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        BoardInvitation::create([
            'board_id' => $board->id,
            'invited_by' => $owner->id,
            'email' => 'late@example.com',
            'role' => 'editor',
            'token' => BoardInvitation::freshToken(),
            'expires_at' => now()->subDay(),
        ]);

        $this->post(route('register'), [
            'name' => 'Late',
            'email' => 'late@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $late = User::where('email', 'late@example.com')->firstOrFail();

        $this->assertDatabaseMissing('board_user', ['user_id' => $late->id]);
    }

    public function test_re_inviting_the_same_address_updates_the_existing_invitation()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)->postJson(route('boards.share.store', $board), [
            'email' => 'newcomer@example.com', 'role' => 'editor',
        ])->assertCreated();

        $this->actingAs($owner)->postJson(route('boards.share.store', $board), [
            'email' => 'newcomer@example.com', 'role' => 'viewer',
        ])->assertCreated()->assertJsonPath('role', 'viewer');

        $this->assertSame(1, BoardInvitation::query()->where('email', 'newcomer@example.com')->count());
    }

    public function test_owner_can_withdraw_an_invitation()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $id = $this->actingAs($owner)->postJson(route('boards.share.store', $board), [
            'email' => 'newcomer@example.com', 'role' => 'editor',
        ])->json('id');

        $this->actingAs($owner)
            ->deleteJson(route('boards.invitations.destroy', [$board, $id]))
            ->assertNoContent();

        $this->assertDatabaseMissing('board_invitations', ['id' => $id]);
    }

    public function test_a_non_owner_cannot_withdraw_an_invitation()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $id = $this->actingAs($owner)->postJson(route('boards.share.store', $board), [
            'email' => 'newcomer@example.com', 'role' => 'editor',
        ])->json('id');

        $this->actingAs($stranger)
            ->deleteJson(route('boards.invitations.destroy', [$board, $id]))
            ->assertNotFound();

        $this->assertDatabaseHas('board_invitations', ['id' => $id]);
    }

    public function test_the_accept_link_sends_a_guest_to_registration()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)->postJson(route('boards.share.store', $board), [
            'email' => 'newcomer@example.com', 'role' => 'editor',
        ]);

        $invitation = BoardInvitation::query()->firstOrFail();

        $this->post(route('logout'));

        $this->get(route('invitations.accept', $invitation->token))
            ->assertRedirect(route('register', ['email' => 'newcomer@example.com']));
    }

    public function test_an_expired_accept_link_is_rejected()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $invitation = BoardInvitation::create([
            'board_id' => $board->id,
            'invited_by' => $owner->id,
            'email' => 'late@example.com',
            'role' => 'editor',
            'token' => BoardInvitation::freshToken(),
            'expires_at' => now()->subDay(),
        ]);

        $this->get(route('invitations.accept', $invitation->token))
            ->assertRedirect(route('home'));
    }

    public function test_sharing_with_an_existing_user_still_attaches_them_rather_than_inviting()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $mate = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('boards.share.store', $board), [
                'email' => $mate->email,
                'role' => 'editor',
            ])
            ->assertCreated()
            ->assertJsonPath('pending', false);

        $this->assertDatabaseCount('board_invitations', 0);
        Notification::assertSentTo($mate, BoardShared::class);
    }
}

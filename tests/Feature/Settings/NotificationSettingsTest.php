<?php

namespace Tests\Feature\Settings;

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\BoardAccessRevoked;
use App\Notifications\BoardRoleChanged;
use App\Notifications\BoardShared;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_settings_page_is_displayed()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notifications.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Notifications')
                ->where('preferences.board_shared', true)
                ->has('types', 3));
    }

    public function test_everything_is_opted_in_until_the_user_says_otherwise()
    {
        $user = User::factory()->create();

        $this->assertNull($user->notification_preferences);
        $this->assertTrue($user->wantsNotification(NotificationType::BoardShared));
        $this->assertTrue($user->wantsNotification(NotificationType::BoardRoleChanged));
    }

    public function test_preferences_can_be_updated()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('notifications.update'), [
                'preferences' => [
                    'board_shared' => false,
                    'board_role_changed' => true,
                    'board_access_revoked' => true,
                ],
            ])
            ->assertRedirect(route('notifications.edit'));

        $this->assertFalse($user->fresh()->wantsNotification(NotificationType::BoardShared));
        $this->assertTrue($user->fresh()->wantsNotification(NotificationType::BoardRoleChanged));
    }

    public function test_unknown_preference_keys_are_rejected()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('notifications.update'), [
                'preferences' => ['board_shared' => false, 'is_admin' => true],
            ])
            ->assertSessionHasErrors('preferences');
    }

    public function test_opting_out_stops_the_share_email()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $mate = User::factory()->create();
        $mate->notification_preferences = ['board_shared' => false];
        $mate->save();

        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)->postJson(route('boards.share.store', $board), [
            'email' => $mate->email,
            'role' => 'editor',
        ]);

        Notification::assertNotSentTo($mate, BoardShared::class);
    }

    public function test_opting_out_of_one_type_leaves_the_others_on()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $mate = User::factory()->create();
        $mate->notification_preferences = ['board_shared' => false];
        $mate->save();

        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($mate->id, ['role' => 'viewer']);

        $this->actingAs($owner)->patchJson(route('boards.share.update', [$board, $mate]), [
            'role' => 'editor',
        ]);

        Notification::assertSentTo($mate, BoardRoleChanged::class);
    }

    public function test_revoking_access_emails_the_collaborator()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $mate = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($mate->id, ['role' => 'editor']);

        $this->actingAs($owner)->deleteJson(route('boards.share.destroy', [$board, $mate]));

        Notification::assertSentTo($mate, BoardAccessRevoked::class);
    }
}

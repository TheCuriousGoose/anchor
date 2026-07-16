<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Covers who may subscribe to a board's presence channel. This is a real access-control
 * boundary: anyone who gets on the channel sees every subsequent change to the board.
 *
 * The suite runs on the `null` broadcaster, whose `auth()` is a no-op and would authorize
 * everyone, so these tests swap in the Pusher-compatible `reverb` driver (signing is local,
 * so no server is involved) to exercise the real authorization path.
 */
class BroadcastChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb' => [
                'driver' => 'reverb',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'app_id' => 'test-app-id',
                'options' => [
                    'host' => '127.0.0.1',
                    'port' => 8080,
                    'scheme' => 'http',
                    'useTLS' => false,
                ],
            ],
        ]);

        // `Broadcast::channel()` registers against whichever driver is default at the time
        // it runs, and channels.php was already loaded (on `null`) while the app booted.
        // Re-running it binds the same definitions to the driver these tests exercise.
        require base_path('routes/channels.php');
    }

    /** @return array<string, mixed> */
    private function authorize(User $user, string $channel): TestResponse
    {
        return $this->actingAs($user)->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $channel,
        ]);
    }

    /** Presence payloads are returned as a JSON string under `channel_data`. */
    private function presenceUserInfo(TestResponse $response): array
    {
        /** @var string $channelData */
        $channelData = $response->json('channel_data');

        return json_decode($channelData, true)['user_info'];
    }

    public function test_owner_can_join_their_board_channel_and_is_announced_as_owner()
    {
        $owner = User::factory()->create(['name' => 'Ada Lovelace']);
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $response = $this->authorize($owner, "presence-boards.{$board->id}")->assertOk();

        $userInfo = $this->presenceUserInfo($response);

        $this->assertSame('Ada Lovelace', $userInfo['name']);
        $this->assertSame('owner', $userInfo['role']);
        $this->assertArrayHasKey('avatar', $userInfo);
    }

    public function test_editor_can_join_and_is_announced_with_their_role()
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($editor->id, ['role' => 'editor']);

        $response = $this->authorize($editor, "presence-boards.{$board->id}")->assertOk();

        $this->assertSame('editor', $this->presenceUserInfo($response)['role']);
    }

    public function test_viewer_can_join_so_read_only_collaborators_still_see_live_updates()
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($viewer->id, ['role' => 'viewer']);

        $response = $this->authorize($viewer, "presence-boards.{$board->id}")->assertOk();

        $this->assertSame('viewer', $this->presenceUserInfo($response)['role']);
    }

    public function test_a_stranger_cannot_join_a_board_channel()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->authorize($stranger, "presence-boards.{$board->id}")->assertForbidden();
    }

    public function test_a_removed_collaborator_can_no_longer_join()
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();

        /** @var Board $board */
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($collaborator->id, ['role' => 'editor']);

        $this->authorize($collaborator, "presence-boards.{$board->id}")->assertOk();

        $board->collaborators()->detach($collaborator->id);

        $this->authorize($collaborator, "presence-boards.{$board->id}")->assertForbidden();
    }

    public function test_guests_cannot_join_a_board_channel()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "presence-boards.{$board->id}",
        ])->assertForbidden();
    }

    public function test_a_user_can_only_join_their_own_user_channel()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->authorize($user, "private-App.Models.User.{$user->id}")->assertOk();
        $this->authorize($user, "private-App.Models.User.{$other->id}")->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

/**
 * Board events are ShouldBroadcastNow, which puts the websocket server in the request path.
 * They are marked ShouldRescue so an unreachable Reverb can't fail a write that has already
 * committed — without it, deleting a board removes the row and *then* returns a 500, leaving
 * the client showing "could not delete" for a board that is already gone.
 */
class BroadcastResilienceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Point broadcasting at a driver that fails the way an unreachable Reverb does. The
     * suite otherwise runs on the `null` driver, which swallows everything and would make
     * these assertions vacuous.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Broadcast::extend('exploding', fn (): Broadcaster => new class implements Broadcaster
        {
            public function auth($request) {}

            public function validAuthenticationResponse($request, $result) {}

            public function broadcast(array $channels, $event, array $payload = [])
            {
                throw new BroadcastException('Pusher error: cURL error 7: Failed to connect');
            }
        });

        config([
            'broadcasting.default' => 'exploding',
            'broadcasting.connections.exploding' => ['driver' => 'exploding'],
        ]);
    }

    public function test_a_board_is_deleted_even_when_the_broadcaster_is_unreachable()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->deleteJson(route('boards.destroy', $board))
            ->assertNoContent();

        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    }

    public function test_a_board_is_renamed_even_when_the_broadcaster_is_unreachable()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Before']);

        $this->actingAs($owner)
            ->patchJson(route('boards.update', $board), ['name' => 'After'])
            ->assertOk();

        $this->assertSame('After', $board->refresh()->name);
    }

    public function test_a_task_is_created_even_when_the_broadcaster_is_unreachable()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('tasks.store', $board), ['title' => 'Ship it'])
            ->assertCreated();

        $this->assertDatabaseHas('tasks', ['board_id' => $board->id, 'title' => 'Ship it']);
    }

    public function test_sharing_a_board_survives_an_unreachable_broadcaster()
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('boards.share.store', $board), [
                'email' => $collaborator->email,
                'role' => 'editor',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('board_user', [
            'board_id' => $board->id,
            'user_id' => $collaborator->id,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BoardSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_share_a_board_with_another_user()
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('boards.share.store', $board), [
                'email' => $collaborator->email,
                'role' => 'editor',
            ])
            ->assertCreated()
            ->assertJsonPath('email', $collaborator->email)
            ->assertJsonPath('role', 'editor');

        $this->assertDatabaseHas('board_user', [
            'board_id' => $board->id,
            'user_id' => $collaborator->id,
            'role' => 'editor',
        ]);
    }

    public function test_non_owner_cannot_share_a_board()
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $other = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($intruder)
            ->postJson(route('boards.share.store', $board), [
                'email' => $other->email,
                'role' => 'editor',
            ])
            ->assertNotFound();
    }

    public function test_editor_collaborator_can_view_and_update_tasks()
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $task = $board->tasks()->create(['title' => 'Ship it']);
        $board->collaborators()->attach($editor->id, ['role' => 'editor']);

        $this->actingAs($editor)
            ->get(route('boards.show', $board))
            ->assertInertia(fn (Assert $page) => $page
                ->where('board.role', 'editor')
                ->where('board.isOwner', false));

        $this->actingAs($editor)
            ->patchJson(route('tasks.update', $task), ['completed' => true])
            ->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'completed' => true]);
    }

    public function test_viewer_collaborator_can_view_but_not_update_tasks()
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $task = $board->tasks()->create(['title' => 'Ship it']);
        $board->collaborators()->attach($viewer->id, ['role' => 'viewer']);

        $this->actingAs($viewer)
            ->get(route('boards.show', $board))
            ->assertOk();

        $this->actingAs($viewer)
            ->patchJson(route('tasks.update', $task), ['completed' => true])
            ->assertNotFound();

        $this->actingAs($viewer)
            ->deleteJson(route('boards.destroy', $board))
            ->assertNotFound();
    }

    public function test_non_collaborator_still_gets_not_found()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($stranger)
            ->get(route('boards.show', $board))
            ->assertNotFound();
    }

    public function test_owner_can_change_a_collaborators_role_and_revoke_access()
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($collaborator->id, ['role' => 'viewer']);

        $this->actingAs($owner)
            ->patchJson(route('boards.share.update', [$board, $collaborator]), ['role' => 'editor'])
            ->assertOk()
            ->assertJsonPath('role', 'editor');

        $this->actingAs($owner)
            ->deleteJson(route('boards.share.destroy', [$board, $collaborator]))
            ->assertNoContent();

        $this->assertDatabaseMissing('board_user', [
            'board_id' => $board->id,
            'user_id' => $collaborator->id,
        ]);
    }

    public function test_cannot_share_a_board_with_its_own_owner()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('boards.share.store', $board), [
                'email' => $owner->email,
                'role' => 'editor',
            ])
            ->assertStatus(422);
    }
}

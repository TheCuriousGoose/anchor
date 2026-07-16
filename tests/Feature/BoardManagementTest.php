<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BoardManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_rename_a_board()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Old name']);

        $this->actingAs($owner)
            ->patchJson(route('boards.update', $board), ['name' => 'New name', 'icon' => '🚀'])
            ->assertOk()
            ->assertJsonPath('name', 'New name')
            ->assertJsonPath('icon', '🚀');

        $this->assertDatabaseHas('boards', ['id' => $board->id, 'name' => 'New name', 'icon' => '🚀']);
    }

    public function test_editor_collaborator_can_rename_a_board_but_viewer_cannot()
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $viewer = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($editor->id, ['role' => 'editor']);
        $board->collaborators()->attach($viewer->id, ['role' => 'viewer']);

        $this->actingAs($editor)
            ->patchJson(route('boards.update', $board), ['name' => 'Renamed by editor'])
            ->assertOk();

        $this->actingAs($viewer)
            ->patchJson(route('boards.update', $board), ['name' => 'Renamed by viewer'])
            ->assertNotFound();
    }

    public function test_boards_index_lists_owned_and_shared_boards()
    {
        $user = User::factory()->create();
        $collaborator = User::factory()->create();

        Carbon::setTestNow(now());
        $user->boards()->create(['name' => 'Mine']);

        Carbon::setTestNow(now()->addMinute());
        $sharedWithMe = $collaborator->boards()->create(['name' => 'Shared with me']);
        $sharedWithMe->collaborators()->attach($user->id, ['role' => 'viewer']);
        $collaborator->boards()->create(['name' => 'Not accessible']);

        $this->actingAs($user)
            ->get(route('boards.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Boards')
                ->has('boards', 2)
                ->where('boards.0.name', 'Shared with me')
                ->where('boards.0.isOwner', false)
                ->where('boards.0.role', 'viewer')
                ->where('boards.1.name', 'Mine')
                ->where('boards.1.isOwner', true));
    }
}

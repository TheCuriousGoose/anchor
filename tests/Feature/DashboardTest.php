<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertInertia(fn(Assert $page) => $page
            ->component('Workspace')
            ->where('board', null));
    }

    public function test_guests_can_use_the_local_workspace()
    {
        $this->get(route('home'))
            ->assertInertia(fn(Assert $page) => $page
                ->component('Workspace')
                ->where('board', null));
    }

    public function test_users_can_create_boards_and_tasks()
    {
        $user = User::factory()->create();

        $boardId = $this->actingAs($user)
            ->postJson(route('boards.store'), [
                'name' => 'Launch',
                'icon' => 'rocket',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Launch')
            ->assertJsonPath('icon', 'rocket')
            ->json('id');

        $this->postJson(route('tasks.store', $boardId), ['title' => 'Write release notes'])
            ->assertCreated()
            ->assertJsonPath('title', 'Write release notes');

        $this->assertDatabaseHas('boards', [
            'id' => $boardId,
            'user_id' => $user->id,
            'icon' => 'rocket',
        ]);
        $this->assertDatabaseHas('tasks', ['board_id' => $boardId, 'title' => 'Write release notes']);
    }

    public function test_users_can_change_a_board_icon()
    {
        $user = User::factory()->create();
        $board = $user->boards()->create(['name' => 'Launch']);

        $this->actingAs($user)
            ->patchJson(route('boards.update', $board), ['icon' => 'plane'])
            ->assertOk()
            ->assertJsonPath('icon', 'plane');

        $this->assertDatabaseHas('boards', ['id' => $board->id, 'icon' => 'plane']);
    }

    public function test_users_can_import_their_guest_board()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('boards.import'), [
                'name' => 'My tasks',
                'icon' => '✓',
                'tasks' => [
                    ['title' => 'Kept open', 'completed' => false],
                    ['title' => 'Already done', 'completed' => true],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'My tasks')
            ->assertJsonCount(2, 'tasks');

        $board = $user->boards()->sole();

        $this->assertDatabaseHas('tasks', [
            'board_id' => $board->id,
            'title' => 'Kept open',
            'completed' => false,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('tasks', [
            'board_id' => $board->id,
            'title' => 'Already done',
            'completed' => true,
            'position' => 1,
        ]);
    }

    public function test_users_cannot_change_tasks_on_another_users_board()
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Private']);
        $task = $board->tasks()->create(['title' => 'Private task']);

        $this->actingAs($intruder)
            ->patchJson(route('tasks.update', $task), ['completed' => true])
            ->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'completed' => false]);
    }

    public function test_dashboard_only_contains_the_users_boards()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = $user->boards()->create(['name' => 'Mine']);
        $otherUser->boards()->create(['name' => 'Not mine']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('boards.show', $board));

        $this->actingAs($user)
            ->get(route('boards.show', $board))
            ->assertInertia(fn(Assert $page) => $page
                ->where('board.name', 'Mine'));
    }

    public function test_users_cannot_view_another_users_board()
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Private']);

        $this->actingAs($intruder)
            ->get(route('boards.show', $board))
            ->assertNotFound();
    }
}

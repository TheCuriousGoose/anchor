<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_task_description()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $task = $board->tasks()->create(['title' => 'Ship it']);

        $this->actingAs($owner)
            ->patchJson(route('tasks.update', $task), ['description' => 'Some notes about this task.'])
            ->assertOk()
            ->assertJsonPath('description', 'Some notes about this task.');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'description' => 'Some notes about this task.']);
    }

    public function test_task_description_is_sanitized_of_scripts_and_event_handlers()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $task = $board->tasks()->create(['title' => 'Ship it']);

        $this->actingAs($owner)
            ->patchJson(route('tasks.update', $task), [
                'description' => '<p onclick="steal()">Hello</p><script>alert(1)</script>',
            ])
            ->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'description' => '<p>Hello</p>']);
    }

    public function test_editor_collaborator_can_update_description_but_viewer_cannot()
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $viewer = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($editor->id, ['role' => 'editor']);
        $board->collaborators()->attach($viewer->id, ['role' => 'viewer']);
        $task = $board->tasks()->create(['title' => 'Ship it']);

        $this->actingAs($editor)
            ->patchJson(route('tasks.update', $task), ['description' => 'Editor update'])
            ->assertOk();

        $this->actingAs($viewer)
            ->patchJson(route('tasks.update', $task), ['description' => 'Viewer update'])
            ->assertNotFound();
    }

    public function test_non_collaborator_cannot_update_description()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Private']);
        $task = $board->tasks()->create(['title' => 'Secret']);

        $this->actingAs($stranger)
            ->patchJson(route('tasks.update', $task), ['description' => 'Hacked'])
            ->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_update_and_delete_labels()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $labelId = $this->actingAs($owner)
            ->postJson(route('labels.store', $board), ['name' => 'Bug', 'color' => 'red'])
            ->assertCreated()
            ->assertJsonPath('name', 'Bug')
            ->assertJsonPath('color', 'red')
            ->json('id');

        $this->assertDatabaseHas('labels', ['id' => $labelId, 'board_id' => $board->id, 'name' => 'Bug']);

        $this->actingAs($owner)
            ->patchJson(route('labels.update', $labelId), ['name' => 'Bugfix', 'color' => 'orange'])
            ->assertOk()
            ->assertJsonPath('name', 'Bugfix')
            ->assertJsonPath('color', 'orange');

        $this->actingAs($owner)
            ->deleteJson(route('labels.destroy', $labelId))
            ->assertNoContent();

        $this->assertDatabaseMissing('labels', ['id' => $labelId]);
    }

    public function test_label_requires_a_known_color()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('labels.store', $board), ['name' => 'Bug', 'color' => 'not-a-color'])
            ->assertInvalid(['color']);
    }

    public function test_viewer_collaborator_cannot_manage_labels()
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $label = $board->labels()->create(['name' => 'Bug', 'color' => 'red']);
        $board->collaborators()->attach($viewer->id, ['role' => 'viewer']);

        $this->actingAs($viewer)
            ->postJson(route('labels.store', $board), ['name' => 'New', 'color' => 'blue'])
            ->assertNotFound();

        $this->actingAs($viewer)
            ->patchJson(route('labels.update', $label), ['name' => 'Hacked'])
            ->assertNotFound();

        $this->actingAs($viewer)
            ->deleteJson(route('labels.destroy', $label))
            ->assertNotFound();
    }

    public function test_task_can_be_assigned_labels_and_a_due_date()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $label = $board->labels()->create(['name' => 'Bug', 'color' => 'red']);
        $task = $board->tasks()->create(['title' => 'Fix crash', 'position' => 0]);

        $this->actingAs($owner)
            ->patchJson(route('tasks.update', $task), [
                'label_ids' => [$label->id],
                'due_date' => '2026-08-01 12:00:00',
            ])
            ->assertOk()
            ->assertJsonPath('labels.0.id', $label->id);

        $this->assertDatabaseHas('label_task', ['label_id' => $label->id, 'task_id' => $task->id]);
        $this->assertNotNull($task->fresh()->due_date);

        $this->actingAs($owner)
            ->patchJson(route('tasks.update', $task), ['label_ids' => []])
            ->assertOk()
            ->assertJsonCount(0, 'labels');

        $this->assertDatabaseMissing('label_task', ['label_id' => $label->id, 'task_id' => $task->id]);
    }

    public function test_task_cannot_be_assigned_a_label_from_another_board()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $otherBoard = $owner->boards()->create(['name' => 'Other']);
        $foreignLabel = $otherBoard->labels()->create(['name' => 'Foreign', 'color' => 'blue']);
        $task = $board->tasks()->create(['title' => 'Fix crash', 'position' => 0]);

        $this->actingAs($owner)
            ->patchJson(route('tasks.update', $task), ['label_ids' => [$foreignLabel->id]])
            ->assertOk()
            ->assertJsonCount(0, 'labels');

        $this->assertDatabaseMissing('label_task', ['label_id' => $foreignLabel->id, 'task_id' => $task->id]);
    }
}

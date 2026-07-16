<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_update_and_delete_notes()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $noteId = $this->actingAs($owner)
            ->postJson(route('notes.store', $board), ['title' => 'Ideas', 'body' => 'Brainstorm list'])
            ->assertCreated()
            ->assertJsonPath('title', 'Ideas')
            ->json('id');

        $this->assertDatabaseHas('notes', ['id' => $noteId, 'board_id' => $board->id, 'title' => 'Ideas']);

        $this->actingAs($owner)
            ->patchJson(route('notes.update', $noteId), ['body' => 'Updated body'])
            ->assertOk()
            ->assertJsonPath('body', 'Updated body');

        $this->actingAs($owner)
            ->deleteJson(route('notes.destroy', $noteId))
            ->assertNoContent();

        $this->assertDatabaseMissing('notes', ['id' => $noteId]);
    }

    public function test_editor_collaborator_can_manage_notes()
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($editor->id, ['role' => 'editor']);

        $this->actingAs($editor)
            ->postJson(route('notes.store', $board), ['title' => 'Editor note', 'body' => 'Body'])
            ->assertCreated();
    }

    public function test_viewer_collaborator_cannot_manage_notes()
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $note = $board->notes()->create(['title' => 'Existing', 'body' => 'Body']);
        $board->collaborators()->attach($viewer->id, ['role' => 'viewer']);

        $this->actingAs($viewer)
            ->postJson(route('notes.store', $board), ['title' => 'New', 'body' => 'Body'])
            ->assertNotFound();

        $this->actingAs($viewer)
            ->patchJson(route('notes.update', $note), ['body' => 'Hacked'])
            ->assertNotFound();

        $this->actingAs($viewer)
            ->deleteJson(route('notes.destroy', $note))
            ->assertNotFound();
    }

    public function test_note_body_is_sanitized_of_scripts_and_event_handlers()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $noteId = $this->actingAs($owner)
            ->postJson(route('notes.store', $board), [
                'title' => 'Ideas',
                'body' => '<p onclick="steal()">Hello</p><script>alert(1)</script>',
            ])
            ->assertCreated()
            ->json('id');

        $this->assertDatabaseHas('notes', ['id' => $noteId, 'body' => '<p>Hello</p>']);
    }

    public function test_non_collaborator_cannot_manage_notes()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Private']);
        $note = $board->notes()->create(['title' => 'Secret', 'body' => 'Body']);

        $this->actingAs($stranger)
            ->postJson(route('notes.store', $board), ['title' => 'New', 'body' => 'Body'])
            ->assertNotFound();

        $this->actingAs($stranger)
            ->patchJson(route('notes.update', $note), ['body' => 'Hacked'])
            ->assertNotFound();
    }
}

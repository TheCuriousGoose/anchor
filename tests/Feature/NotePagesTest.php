<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_nested_sub_page()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $parent = $board->notes()->create(['title' => 'Parent page', 'body' => '']);

        $childId = $this->actingAs($owner)
            ->postJson(route('notes.store', $board), ['title' => 'Child page', 'parent_id' => $parent->id])
            ->assertCreated()
            ->assertJsonPath('parent_id', $parent->id)
            ->json('id');

        $this->assertDatabaseHas('notes', ['id' => $childId, 'parent_id' => $parent->id]);
    }

    public function test_deleting_a_parent_page_cascades_to_its_children()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $parent = $board->notes()->create(['title' => 'Parent page', 'body' => '']);
        $child = $board->notes()->create(['title' => 'Child page', 'body' => '', 'parent_id' => $parent->id]);
        $grandchild = $board->notes()->create(['title' => 'Grandchild page', 'body' => '', 'parent_id' => $child->id]);

        $this->actingAs($owner)
            ->deleteJson(route('notes.destroy', $parent))
            ->assertNoContent();

        $this->assertDatabaseMissing('notes', ['id' => $parent->id]);
        $this->assertDatabaseMissing('notes', ['id' => $child->id]);
        $this->assertDatabaseMissing('notes', ['id' => $grandchild->id]);
    }

    public function test_parent_id_from_a_different_board_is_rejected()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $otherBoard = $owner->boards()->create(['name' => 'Other']);
        $foreignParent = $otherBoard->notes()->create(['title' => 'Foreign page', 'body' => '']);

        $this->actingAs($owner)
            ->postJson(route('notes.store', $board), ['title' => 'Child', 'parent_id' => $foreignParent->id])
            ->assertUnprocessable();
    }
}

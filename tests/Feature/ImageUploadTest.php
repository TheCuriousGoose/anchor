<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_owner_can_upload_an_image_to_a_note()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $note = $board->notes()->create(['title' => 'Ideas', 'body' => '']);

        $response = $this->actingAs($owner)
            ->postJson(route('notes.images.store', $note), [
                'image' => UploadedFile::fake()->image('photo.jpg', 200, 200),
            ]);

        $response->assertCreated()->assertJsonStructure(['url']);
        $this->assertCount(1, $note->fresh()->getMedia('content-images'));
    }

    public function test_owner_can_upload_an_image_to_a_task()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $task = $board->tasks()->create(['title' => 'Ship it']);

        $response = $this->actingAs($owner)
            ->postJson(route('tasks.images.store', $task), [
                'image' => UploadedFile::fake()->image('photo.jpg', 200, 200),
            ]);

        $response->assertCreated()->assertJsonStructure(['url']);
        $this->assertCount(1, $task->fresh()->getMedia('content-images'));
    }

    public function test_non_image_uploads_are_rejected()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $note = $board->notes()->create(['title' => 'Ideas', 'body' => '']);

        $this->actingAs($owner)
            ->postJson(route('notes.images.store', $note), [
                'image' => UploadedFile::fake()->create('document.pdf', 100),
            ])
            ->assertUnprocessable();

        $this->assertCount(0, $note->fresh()->getMedia('content-images'));
    }

    public function test_oversized_uploads_are_rejected()
    {
        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $note = $board->notes()->create(['title' => 'Ideas', 'body' => '']);

        $this->actingAs($owner)
            ->postJson(route('notes.images.store', $note), [
                'image' => UploadedFile::fake()->image('big.jpg')->size(6000),
            ])
            ->assertUnprocessable();

        $this->assertCount(0, $note->fresh()->getMedia('content-images'));
    }

    public function test_viewer_collaborator_cannot_upload_images()
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($viewer->id, ['role' => 'viewer']);
        $note = $board->notes()->create(['title' => 'Ideas', 'body' => '']);
        $task = $board->tasks()->create(['title' => 'Ship it']);

        $this->actingAs($viewer)
            ->postJson(route('notes.images.store', $note), [
                'image' => UploadedFile::fake()->image('photo.jpg', 200, 200),
            ])
            ->assertNotFound();

        $this->actingAs($viewer)
            ->postJson(route('tasks.images.store', $task), [
                'image' => UploadedFile::fake()->image('photo.jpg', 200, 200),
            ])
            ->assertNotFound();
    }

    public function test_non_collaborator_cannot_upload_images()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Private']);
        $note = $board->notes()->create(['title' => 'Secret', 'body' => '']);

        $this->actingAs($stranger)
            ->postJson(route('notes.images.store', $note), [
                'image' => UploadedFile::fake()->image('photo.jpg', 200, 200),
            ])
            ->assertNotFound();
    }
}

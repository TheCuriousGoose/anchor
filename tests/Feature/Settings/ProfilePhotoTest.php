<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_user_can_upload_a_profile_photo()
    {
        $user = User::factory()->create();
        $this->assertNull($user->avatar);

        $response = $this->actingAs($user)
            ->post(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('avatar.jpg', 800, 800),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringContainsString('.webp', $user->avatar);
        $this->assertCount(1, $user->getMedia('avatar'));
    }

    public function test_uploading_a_new_photo_replaces_the_previous_one()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('first.jpg', 800, 800),
        ]);

        $this->actingAs($user)->post(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('second.jpg', 800, 800),
        ]);

        $this->assertCount(1, $user->fresh()->getMedia('avatar'));
    }

    public function test_non_image_uploads_are_rejected()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->create('document.pdf', 100),
            ]);

        $response->assertSessionHasErrors('photo');
        $this->assertCount(0, $user->fresh()->getMedia('avatar'));
    }

    public function test_user_can_remove_their_profile_photo()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('avatar.jpg', 800, 800),
        ]);

        $this->assertNotNull($user->fresh()->avatar);

        $response = $this->actingAs($user)->delete(route('profile.photo.destroy'));

        $response->assertRedirect(route('profile.edit'));
        $this->assertNull($user->fresh()->avatar);
    }
}

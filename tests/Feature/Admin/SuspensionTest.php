<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_users_are_logged_out_of_the_application()
    {
        $user = User::factory()->suspended()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * Suspension is enforced per-request rather than only at login, so a session that was
     * already open when the suspension landed is terminated too.
     */
    public function test_an_active_session_is_terminated_once_the_user_is_suspended()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertSuccessful();

        $user->suspended_at = now();
        $user->save();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_suspended_users_cannot_log_in()
    {
        $user = User::factory()->suspended()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_suspended_users_receive_a_forbidden_response_on_json_requests()
    {
        $user = User::factory()->create();
        $board = $user->boards()->create(['name' => 'Roadmap']);

        $user->suspended_at = now();
        $user->save();

        $this->actingAs($user)
            ->postJson(route('tasks.store', $board), ['title' => 'Blocked'])
            ->assertForbidden();
    }

    public function test_unsuspended_users_can_use_the_application_again()
    {
        $user = User::factory()->suspended()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));

        $user->suspended_at = null;
        $user->save();

        $this->actingAs($user)->get(route('dashboard'))->assertSuccessful();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_layout_uses_a_post_form_for_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('index'))
            ->assertOk()
            ->assertSee('action="'.route('logout').'" method="POST"', false)
            ->assertSee('class="dropdown-item dropdown-item-button"', false)
            ->assertDontSee('href="'.route('logout').'"', false);
    }

    public function test_logout_post_ends_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_direct_logout_url_redirects_instead_of_showing_an_error(): void
    {
        $this->get('/logout')
            ->assertRedirect(route('login'));
    }
}

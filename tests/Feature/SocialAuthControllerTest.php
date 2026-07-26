<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_supported_provider_redirects_to_oauth_consent_page(): void
    {
        $this->configureProvider('google');

        $provider = Mockery::mock();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://accounts.example.test/oauth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('social.redirect', 'google'))
            ->assertRedirect('https://accounts.example.test/oauth');
    }

    public function test_new_social_user_is_created_as_verified_student_and_logged_in(): void
    {
        $this->mockSocialUser('google', [
            'id' => 'google-user-1',
            'name' => 'Maria Student',
            'email' => 'Maria@example.com',
        ]);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('index'));

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('student', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-user-1',
        ]);
    }

    public function test_existing_student_is_linked_by_email_without_duplicate_user(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'student',
            'email' => 'student@example.com',
        ]);

        $this->mockSocialUser('facebook', [
            'id' => 'facebook-user-1',
            'name' => 'Updated Provider Name',
            'email' => 'STUDENT@example.com',
        ]);

        $this->get(route('social.callback', 'facebook'))
            ->assertRedirect(route('index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::where('email', 'student@example.com')->count());
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'facebook',
            'provider_user_id' => 'facebook-user-1',
        ]);
    }

    public function test_existing_social_identity_logs_into_linked_user_even_if_provider_email_changes(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email' => 'original@example.com',
        ]);
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'stable-google-id',
        ]);

        $this->mockSocialUser('google', [
            'id' => 'stable-google-id',
            'name' => 'Maria Student',
            'email' => 'changed@example.com',
        ]);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('index'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('users', ['email' => 'changed@example.com']);
    }

    public function test_social_login_does_not_automatically_link_staff_account(): void
    {
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $this->mockSocialUser('google', [
            'id' => 'google-admin-id',
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('social_auth_error');

        $this->assertGuest();
        $this->assertDatabaseMissing('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'google-admin-id',
        ]);
    }

    public function test_social_login_without_email_fails_safely(): void
    {
        $this->mockSocialUser('facebook', [
            'id' => 'facebook-no-email',
            'name' => 'No Email',
            'email' => null,
        ]);

        $this->get(route('social.callback', 'facebook'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('social_auth_error');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_unconfigured_provider_returns_to_login_without_contacting_provider(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        Socialite::shouldReceive('driver')->never();

        $this->get(route('social.redirect', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('social_auth_error');
    }

    public function test_provider_callback_failure_returns_to_login_without_creating_account(): void
    {
        $this->configureProvider('google');

        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andThrow(new \RuntimeException('OAuth cancelled'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('social_auth_error');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_different_provider_identity_cannot_replace_existing_link_for_same_student(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email' => 'student@example.com',
        ]);
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'original-google-id',
        ]);

        $this->mockSocialUser('google', [
            'id' => 'different-google-id',
            'name' => 'Student',
            'email' => 'student@example.com',
        ]);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('social_auth_error');

        $this->assertGuest();
        $this->assertDatabaseMissing('social_accounts', [
            'provider_user_id' => 'different-google-id',
        ]);
    }

    public function test_linked_student_profile_is_redirected_to_student_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email' => 'student@example.com',
        ]);
        Student::factory()->create(['user_id' => $user->id]);

        $this->mockSocialUser('facebook', [
            'id' => 'facebook-student-id',
            'name' => 'Student',
            'email' => 'student@example.com',
        ]);

        $this->get(route('social.callback', 'facebook'))
            ->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_unsupported_provider_returns_not_found(): void
    {
        $this->get('/auth/unknown/redirect')->assertNotFound();
        $this->get('/auth/unknown/callback')->assertNotFound();
    }

    private function configureProvider(string $provider): void
    {
        config([
            "services.{$provider}.client_id" => 'client-id',
            "services.{$provider}.client_secret" => 'client-secret',
            "services.{$provider}.redirect" => "/auth/{$provider}/callback",
        ]);
    }

    private function mockSocialUser(string $providerName, array $attributes): void
    {
        $this->configureProvider($providerName);

        $socialUser = (new SocialiteUser)->map($attributes);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with($providerName)
            ->andReturn($provider);
    }
}

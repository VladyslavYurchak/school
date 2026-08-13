<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_open_settings_and_see_zoom_and_telegram_controls(): void
    {
        [$user] = $this->createTeacherUser();

        $this
            ->actingAs($user)
            ->get(route('teacher.settings.edit'))
            ->assertOk()
            ->assertSee('Налаштування')
            ->assertSee('Ваше постійне посилання на Zoom')
            ->assertSee('Підключити Telegram')
            ->assertSee(route('teacher.telegram.connect'), false);
    }

    public function test_teacher_can_save_and_remove_own_zoom_link(): void
    {
        [$user, $teacher] = $this->createTeacherUser();

        $this
            ->actingAs($user)
            ->patch(route('teacher.settings.update'), [
                'meeting_url' => 'https://zoom.us/j/123456789',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(
            'https://zoom.us/j/123456789',
            $teacher->fresh()->meeting_url,
        );

        $this
            ->actingAs($user)
            ->patch(route('teacher.settings.update'), ['meeting_url' => ''])
            ->assertRedirect();

        $this->assertNull($teacher->fresh()->meeting_url);
    }

    public function test_invalid_zoom_link_is_rejected(): void
    {
        [$user, $teacher] = $this->createTeacherUser();

        $this
            ->actingAs($user)
            ->from(route('teacher.settings.edit'))
            ->patch(route('teacher.settings.update'), [
                'meeting_url' => 'not-a-link',
            ])
            ->assertRedirect(route('teacher.settings.edit'))
            ->assertSessionHasErrors('meeting_url');

        $this->assertNull($teacher->fresh()->meeting_url);
    }

    public function test_student_and_admin_cannot_open_or_update_teacher_settings(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($student)
            ->get(route('teacher.settings.edit'))
            ->assertForbidden();
        $this->actingAs($student)
            ->patch(route('teacher.settings.update'), ['meeting_url' => 'https://zoom.us/j/1'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('teacher.settings.edit'))
            ->assertForbidden();
        $this->actingAs($admin)
            ->patch(route('teacher.settings.update'), ['meeting_url' => 'https://zoom.us/j/1'])
            ->assertForbidden();
    }

    public function test_connected_telegram_account_is_shown_on_settings_page(): void
    {
        [$user] = $this->createTeacherUser();
        TelegramAccount::query()->create([
            'user_id' => $user->id,
            'telegram_user_id' => '12345',
            'chat_id' => '12345',
            'username' => 'teacher_account',
            'notifications_enabled' => true,
            'connected_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->get(route('teacher.settings.edit'))
            ->assertOk()
            ->assertSee('@teacher_account')
            ->assertSee('Від’єднати Telegram')
            ->assertSee(route('teacher.telegram.disconnect'), false);
    }

    private function createTeacherUser(): array
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'email_verified_at' => now(),
        ]);
        $teacher = Teacher::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        return [$user, $teacher];
    }
}

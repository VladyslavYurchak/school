<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTeacherPublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_teacher_edit_page_with_public_profile_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create([
            'public_position' => 'Викладачка англійської мови',
            'public_details' => 'Досвід: 6 років',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertSee('Посада біля імені')
            ->assertSee('Факти під фото')
            ->assertSee('Викладачка англійської мови')
            ->assertSee('Досвід: 6 років');
    }

    public function test_admin_can_update_teacher_public_profile_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::factory()->create([
            'user_id' => $teacherUser->id,
            'first_name' => 'Даша',
            'last_name' => 'Юрчак',
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.teachers.update', $teacher), [
                'user_id' => $teacherUser->id,
                'first_name' => 'Даша',
                'last_name' => 'Юрчак',
                'phone' => '+380000000000',
                'lesson_price' => 500,
                'group_lesson_price' => 300,
                'pair_lesson_price' => 400,
                'trial_lesson_price' => 200,
                'note' => 'Internal note',
                'is_active' => 1,
                'public_position' => 'Викладачка англійської мови',
                'public_bio' => '<p style="color:red">Перший абзац.</p><p><em>Другий абзац.</em></p><script>alert("x")</script>',
                'public_details' => "Досвід: 6 років\nФормат: онлайн/офлайн",
                'is_public' => 1,
                'public_sort_order' => 3,
            ])
            ->assertRedirect(route('admin.teachers.edit', $teacher));

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'public_position' => 'Викладачка англійської мови',
            'public_bio' => '<p>Перший абзац.</p><p><em>Другий абзац.</em></p>',
            'public_details' => "Досвід: 6 років\nФормат: онлайн/офлайн",
            'is_public' => true,
            'public_sort_order' => 3,
        ]);
    }

    public function test_admin_can_upload_and_preview_teacher_public_photo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

        $this
            ->actingAs($admin)
            ->put(route('admin.teachers.update', $teacher), [
                'user_id' => $teacherUser->id,
                'first_name' => $teacher->first_name,
                'last_name' => $teacher->last_name,
                'is_active' => 1,
                'is_public' => 1,
                'public_photo' => UploadedFile::fake()->image('teacher.jpg', 800, 800)->size(900),
            ])
            ->assertRedirect(route('admin.teachers.edit', $teacher))
            ->assertSessionHas('success');

        $teacher->refresh();
        Storage::disk('public')->assertExists($teacher->public_photo);

        $this
            ->actingAs($admin)
            ->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertSee('id="teacher-photo-preview"', false)
            ->assertSee(asset('storage/'.$teacher->public_photo), false)
            ->assertSee('teacher-profile-actions', false);
    }
}

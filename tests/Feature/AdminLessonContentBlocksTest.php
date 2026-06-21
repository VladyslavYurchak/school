<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\LessonTest;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminLessonContentBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Storage::fake('public');
    }

    public function test_main_block_update_saves_content_youtube_audio_and_allowed_material_files(): void
    {
        $lesson = $this->createLesson();

        $this
            ->put(route('admin.course.lesson.main.update', $lesson), [
                'content' => '<p>Class work</p>',
                'video_url' => 'youtu.be/abc123',
                'audio_file' => UploadedFile::fake()->create('listening.mp3', 128, 'audio/mpeg'),
                'media_files' => [
                    UploadedFile::fake()->create('worksheet.pdf', 64, 'application/pdf'),
                    UploadedFile::fake()->create('notes.docx', 64, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.main.edit', $lesson));

        $lesson->refresh();

        $this->assertSame('<p>Class work</p>', $lesson->content);
        $this->assertSame('https://www.youtube.com/embed/abc123', $lesson->video_url);
        $this->assertNotNull($lesson->audio_file);
        $this->assertCount(2, $lesson->media_files);
        $this->assertStringStartsWith('lesson_audio/', $lesson->audio_file);

        Storage::disk('public')->assertExists($lesson->audio_file);

        foreach ($lesson->media_files as $file) {
            $this->assertStringStartsWith('lesson_media/', $file);
            Storage::disk('public')->assertExists($file);
        }
    }

    public function test_main_block_rejects_disallowed_material_file_type(): void
    {
        $lesson = $this->createLesson();

        $this
            ->from(route('admin.course.lesson.main.edit', $lesson))
            ->put(route('admin.course.lesson.main.update', $lesson), [
                'media_files' => [
                    UploadedFile::fake()->create('danger.exe', 10, 'application/x-msdownload'),
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.main.edit', $lesson))
            ->assertSessionHasErrors('media_files.0');
    }

    public function test_main_block_delete_file_removes_full_stored_path_from_storage_and_database(): void
    {
        $lesson = $this->createLesson([
            'media_files' => ['lesson_media/worksheet.pdf', 'lesson_media/notes.docx'],
        ]);

        Storage::disk('public')->put('lesson_media/worksheet.pdf', 'pdf');
        Storage::disk('public')->put('lesson_media/notes.docx', 'doc');

        $this
            ->delete(route('admin.course.lesson.main.file.delete', [
                'lesson' => $lesson,
                'filename' => 'lesson_media/worksheet.pdf',
            ]))
            ->assertRedirect();

        $lesson->refresh();

        Storage::disk('public')->assertMissing('lesson_media/worksheet.pdf');
        Storage::disk('public')->assertExists('lesson_media/notes.docx');
        $this->assertSame(['lesson_media/notes.docx'], $lesson->media_files);
    }

    public function test_homework_update_saves_text_youtube_and_allowed_files(): void
    {
        $lesson = $this->createLesson();

        $this
            ->put(route('admin.course.lesson.homework.update', $lesson), [
                'homework_text' => '<p>Homework</p>',
                'homework_video_url' => 'youtube.com/watch?v=home123',
                'homework_files' => [
                    UploadedFile::fake()->image('picture.jpg'),
                    UploadedFile::fake()->create('homework.doc', 64, 'application/msword'),
                    UploadedFile::fake()->create('listening.mp3', 128, 'audio/mpeg'),
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.homework.edit', $lesson));

        $lesson->refresh();

        $this->assertSame('<p>Homework</p>', $lesson->homework_text);
        $this->assertSame('https://www.youtube.com/embed/home123', $lesson->homework_video_url);
        $this->assertCount(3, $lesson->homework_files);

        foreach ($lesson->homework_files as $file) {
            $this->assertStringStartsWith('homework_files/', $file);
            Storage::disk('public')->assertExists($file);
        }
    }

    public function test_homework_rejects_disallowed_file_type(): void
    {
        $lesson = $this->createLesson();

        $this
            ->from(route('admin.course.lesson.homework.edit', $lesson))
            ->put(route('admin.course.lesson.homework.update', $lesson), [
                'homework_files' => [
                    UploadedFile::fake()->create('danger.exe', 10, 'application/x-msdownload'),
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.homework.edit', $lesson))
            ->assertSessionHasErrors('homework_files.0');
    }

    public function test_homework_delete_file_removes_full_stored_path_from_storage_and_database(): void
    {
        $lesson = $this->createLesson([
            'homework_files' => ['homework_files/task.pdf', 'homework_files/audio.mp3'],
        ]);

        Storage::disk('public')->put('homework_files/task.pdf', 'pdf');
        Storage::disk('public')->put('homework_files/audio.mp3', 'audio');

        $this
            ->delete(route('admin.course.lesson.homework.file.delete', [
                'lesson' => $lesson,
                'filename' => 'homework_files/task.pdf',
            ]))
            ->assertRedirect();

        $lesson->refresh();

        Storage::disk('public')->assertMissing('homework_files/task.pdf');
        Storage::disk('public')->assertExists('homework_files/audio.mp3');
        $this->assertSame(['homework_files/audio.mp3'], $lesson->homework_files);
    }

    public function test_lesson_content_homework_and_test_pages_use_unified_admin_layout(): void
    {
        $lesson = $this->createLesson([
            'content' => '<p>Main task</p>',
            'homework_text' => '<p>Homework task</p>',
            'media_files' => ['lesson_media/worksheet.pdf'],
            'homework_files' => ['homework_files/task.pdf'],
        ]);

        $test = LessonTest::create([
            'lesson_id' => $lesson->id,
            'question' => 'Choose the correct answer',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);

        $test->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
            ['option_text' => 'C', 'is_correct' => false],
        ]);

        foreach ([
            route('admin.course.lesson.main.create', $lesson),
            route('admin.course.lesson.main.edit', $lesson),
            route('admin.course.lesson.homework.create', $lesson),
            route('admin.course.lesson.homework.edit', $lesson),
            route('admin.course.lesson.test.create', $lesson),
            route('admin.course.lesson.test.edit', [$lesson, $test]),
            route('admin.course.lesson.edit', $lesson),
        ] as $url) {
            $response = $this
                ->get($url)
                ->assertOk()
                ->assertSee('class="admin-page', false)
                ->assertSee('class="admin-hero"', false)
                ->assertSee('admin-panel', false)
                ->assertDontSee('<style>', false)
                ->assertDontSee('style="', false);

            $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
        }
    }

    private function createLesson(array $attributes = []): Lesson
    {
        $language = Language::create(['name' => 'English']);
        $course = Course::create([
            'title' => 'English A1',
            'description' => 'Course description',
            'language_id' => $language->id,
            'price' => 0,
            'is_published' => true,
        ]);

        return Lesson::create(array_merge([
            'course_id' => $course->id,
            'title' => 'Lesson 1',
            'description' => 'Lesson description',
            'position' => 1,
            'is_published' => true,
        ], $attributes));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\LessonContentBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminLessonBlockBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_builder_page_offers_all_supported_block_types(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.blocks.index', $lesson))
            ->assertOk()
            ->assertSee('Конструктор уроку')
            ->assertSee('Текст')
            ->assertSee('YouTube-відео')
            ->assertSee('Аудіо')
            ->assertSee('Зображення')
            ->assertSee('PDF')
            ->assertSee(route('admin.course.lesson.blocks.create', ['lesson' => $lesson, 'type' => 'text']), false)
            ->assertSee(route('admin.course.lesson.blocks.create', ['lesson' => $lesson, 'type' => 'pdf']), false)
            ->assertDontSee('<style>', false);
    }

    public function test_admin_can_create_repeated_text_blocks_with_safe_rich_text(): void
    {
        $lesson = $this->createLesson();

        foreach (['Вступ', 'Пояснення'] as $index => $title) {
            $this->actingAs($this->admin)
                ->post(route('admin.course.lesson.blocks.store', $lesson), [
                    'type' => 'text',
                    'title' => $title,
                    'content' => '<p class="bad">Text '.$index.'</p><script>alert(1)</script>',
                    'is_active' => 1,
                ])
                ->assertRedirect(route('admin.course.lesson.blocks.index', $lesson));
        }

        $blocks = $lesson->contentBlocks()->get();

        $this->assertCount(2, $blocks);
        $this->assertSame([1, 2], $blocks->pluck('position')->all());
        $this->assertSame('<p>Text 0</p>', $blocks[0]->content);
        $this->assertSame('<p>Text 1</p>', $blocks[1]->content);
    }

    public function test_video_block_accepts_only_youtube_and_normalizes_embed_url(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.blocks.store', $lesson), [
                'type' => 'video',
                'title' => 'Grammar video',
                'video_url' => 'youtu.be/abc123?t=30',
                'content' => '<p>Watch carefully.</p>',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lesson_content_blocks', [
            'lesson_id' => $lesson->id,
            'type' => 'video',
            'video_url' => 'https://www.youtube.com/embed/abc123',
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.course.lesson.blocks.create', ['lesson' => $lesson, 'type' => 'video']))
            ->post(route('admin.course.lesson.blocks.store', $lesson), [
                'type' => 'video',
                'video_url' => 'https://vimeo.com/123',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('video_url');
    }

    public function test_audio_image_and_pdf_blocks_store_expected_files(): void
    {
        $lesson = $this->createLesson();
        $files = [
            'audio' => UploadedFile::fake()->create('dialogue.mp3', 300, 'audio/mpeg'),
            'image' => UploadedFile::fake()->image('grammar.png'),
            'pdf' => UploadedFile::fake()->create('worksheet.pdf', 500, 'application/pdf'),
        ];

        foreach ($files as $type => $file) {
            $this->actingAs($this->admin)
                ->post(route('admin.course.lesson.blocks.store', $lesson), [
                    'type' => $type,
                    'title' => ucfirst($type),
                    'media_file' => $file,
                    'is_active' => 1,
                ])
                ->assertRedirect();
        }

        foreach ($lesson->contentBlocks()->get() as $block) {
            $this->assertStringStartsWith('lesson_blocks/'.$block->type.'/', $block->media_path);
            Storage::disk('public')->assertExists($block->media_path);
            $this->assertNotNull($block->media_name);
            $this->assertGreaterThan(0, $block->media_size);
        }
    }

    public function test_media_block_rejects_file_of_wrong_type(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.blocks.store', $lesson), [
                'type' => 'pdf',
                'media_file' => UploadedFile::fake()->create('danger.exe', 10, 'application/x-msdownload'),
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('media_file');

        $this->assertDatabaseCount('lesson_content_blocks', 0);
    }

    public function test_updating_media_replaces_and_deletes_previous_file(): void
    {
        $lesson = $this->createLesson();
        $block = $lesson->contentBlocks()->create([
            'type' => 'pdf',
            'media_path' => 'lesson_blocks/pdf/old.pdf',
            'media_name' => 'old.pdf',
            'position' => 1,
            'is_active' => true,
        ]);
        Storage::disk('public')->put($block->media_path, 'old');

        $this->actingAs($this->admin)
            ->put(route('admin.course.lesson.blocks.update', [$lesson, $block]), [
                'type' => 'pdf',
                'title' => 'Updated PDF',
                'media_file' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.course.lesson.blocks.index', $lesson));

        $block->refresh();
        Storage::disk('public')->assertMissing('lesson_blocks/pdf/old.pdf');
        Storage::disk('public')->assertExists($block->media_path);
        $this->assertSame('new.pdf', $block->media_name);
    }

    public function test_changing_media_block_to_text_deletes_and_clears_old_file(): void
    {
        $lesson = $this->createLesson();
        $block = $lesson->contentBlocks()->create([
            'type' => 'pdf',
            'media_path' => 'lesson_blocks/pdf/old.pdf',
            'media_name' => 'old.pdf',
            'media_mime' => 'application/pdf',
            'media_size' => 100,
            'position' => 1,
            'is_active' => true,
        ]);
        Storage::disk('public')->put($block->media_path, 'old');

        $this->actingAs($this->admin)
            ->put(route('admin.course.lesson.blocks.update', [$lesson, $block]), [
                'type' => 'text',
                'title' => 'Text now',
                'content' => '<p>Updated text</p>',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.course.lesson.blocks.index', $lesson));

        $block->refresh();

        Storage::disk('public')->assertMissing('lesson_blocks/pdf/old.pdf');
        $this->assertNull($block->media_path);
        $this->assertNull($block->media_name);
        $this->assertNull($block->media_mime);
        $this->assertNull($block->media_size);
    }

    public function test_changing_between_media_types_requires_a_matching_new_file(): void
    {
        $lesson = $this->createLesson();
        $block = $lesson->contentBlocks()->create([
            'type' => 'audio',
            'media_path' => 'lesson_blocks/audio/old.mp3',
            'media_name' => 'old.mp3',
            'media_mime' => 'audio/mpeg',
            'media_size' => 100,
            'position' => 1,
            'is_active' => true,
        ]);
        Storage::disk('public')->put($block->media_path, 'old');

        $this->actingAs($this->admin)
            ->from(route('admin.course.lesson.blocks.edit', [$lesson, $block]))
            ->put(route('admin.course.lesson.blocks.update', [$lesson, $block]), [
                'type' => 'image',
                'title' => 'Image now',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('media_file');

        $this->assertSame('audio', $block->fresh()->type);
        Storage::disk('public')->assertExists('lesson_blocks/audio/old.mp3');
    }

    public function test_admin_cannot_edit_or_delete_block_from_another_lesson(): void
    {
        $firstLesson = $this->createLesson();
        $secondLesson = $this->createLesson($firstLesson->course);
        $block = $firstLesson->contentBlocks()->create([
            'type' => 'text',
            'content' => '<p>Private block</p>',
            'position' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.blocks.edit', [$secondLesson, $block]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->delete(route('admin.course.lesson.blocks.destroy', [$secondLesson, $block]))
            ->assertNotFound();

        $this->assertDatabaseHas('lesson_content_blocks', ['id' => $block->id]);
    }

    public function test_admin_can_reorder_and_hide_blocks(): void
    {
        $lesson = $this->createLesson();
        $first = $this->createTextBlock($lesson, 'First', 1);
        $second = $this->createTextBlock($lesson, 'Second', 2);
        $third = $this->createTextBlock($lesson, 'Third', 3);

        $this->actingAs($this->admin)
            ->postJson(route('admin.course.lesson.blocks.order', $lesson), [
                'blocks' => [$third->id, $first->id, $second->id],
            ])
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertSame(
            [$third->id, $first->id, $second->id],
            $lesson->contentBlocks()->pluck('id')->all()
        );

        $this->actingAs($this->admin)
            ->patch(route('admin.course.lesson.blocks.toggle', [$lesson, $first]))
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_active);
    }

    public function test_block_reorder_requires_complete_list_of_current_lesson_blocks(): void
    {
        $lesson = $this->createLesson();
        $first = $this->createTextBlock($lesson, 'First', 1);
        $second = $this->createTextBlock($lesson, 'Second', 2);

        $this->actingAs($this->admin)
            ->postJson(route('admin.course.lesson.blocks.order', $lesson), [
                'blocks' => [$second->id],
            ])
            ->assertUnprocessable();

        $this->assertSame([$first->id, $second->id], $lesson->contentBlocks()->pluck('id')->all());
    }

    public function test_destroying_media_block_deletes_file_and_normalizes_positions(): void
    {
        $lesson = $this->createLesson();
        $first = $this->createTextBlock($lesson, 'First', 1);
        $pdf = $lesson->contentBlocks()->create([
            'type' => 'pdf',
            'media_path' => 'lesson_blocks/pdf/task.pdf',
            'media_name' => 'task.pdf',
            'position' => 2,
            'is_active' => true,
        ]);
        $third = $this->createTextBlock($lesson, 'Third', 3);
        Storage::disk('public')->put($pdf->media_path, 'pdf');

        $this->actingAs($this->admin)
            ->delete(route('admin.course.lesson.blocks.destroy', [$lesson, $pdf]))
            ->assertRedirect();

        Storage::disk('public')->assertMissing('lesson_blocks/pdf/task.pdf');
        $this->assertDatabaseMissing('lesson_content_blocks', ['id' => $pdf->id]);
        $this->assertSame([1, 2], $lesson->contentBlocks()->pluck('position')->all());
        $this->assertSame([$first->id, $third->id], $lesson->contentBlocks()->pluck('id')->all());
    }

    public function test_admin_lesson_preview_renders_blocks_before_tests_and_homework(): void
    {
        $lesson = $this->createLesson();
        $this->createTextBlock($lesson, 'Block material', 1);
        $lesson->update(['homework_text' => '<p>Homework material</p>']);
        $lesson->tests()->create([
            'question' => 'Final question',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.show', $lesson))
            ->assertOk()
            ->assertSee('Block material')
            ->assertSee('Final question')
            ->assertSee('Homework material')
            ->getContent();

        $this->assertLessThan(strpos($html, 'Final question'), strpos($html, 'Block material'));
        $this->assertLessThan(strpos($html, 'Homework material'), strpos($html, 'Final question'));
    }

    public function test_deleting_lesson_removes_its_block_files(): void
    {
        $lesson = $this->createLesson();
        $lesson->update([
            'audio_file' => 'lesson_audio/legacy.mp3',
            'media_files' => ['lesson_media/legacy.pdf'],
            'homework_files' => ['homework_files/legacy.docx'],
        ]);
        Storage::disk('public')->put($lesson->audio_file, 'audio');
        Storage::disk('public')->put($lesson->media_files[0], 'pdf');
        Storage::disk('public')->put($lesson->homework_files[0], 'docx');
        $block = $lesson->contentBlocks()->create([
            'type' => 'audio',
            'media_path' => 'lesson_blocks/audio/dialogue.mp3',
            'media_name' => 'dialogue.mp3',
            'position' => 1,
            'is_active' => true,
        ]);
        Storage::disk('public')->put($block->media_path, 'audio');
        $exercise = $lesson->exercises()->create([
            'type' => 'dictation',
            'title' => 'Dictation',
            'position' => 1,
            'is_active' => true,
        ]);
        $exerciseItem = $exercise->items()->create([
            'prompt' => '',
            'answer' => 'Hello.',
            'audio_path' => 'lesson-exercises/audio/hello.mp3',
            'position' => 1,
        ]);
        Storage::disk('public')->put($exerciseItem->audio_path, 'audio');

        $this->actingAs($this->admin)
            ->delete(route('admin.course.lesson.delete', $lesson))
            ->assertRedirect(route('admin.course.show', $lesson->course_id));

        Storage::disk('public')->assertMissing($block->media_path);
        Storage::disk('public')->assertMissing($exerciseItem->audio_path);
        Storage::disk('public')->assertMissing('lesson_audio/legacy.mp3');
        Storage::disk('public')->assertMissing('lesson_media/legacy.pdf');
        Storage::disk('public')->assertMissing('homework_files/legacy.docx');
        $this->assertDatabaseMissing('lesson_content_blocks', ['id' => $block->id]);
        $this->assertDatabaseMissing('lesson_exercise_items', ['id' => $exerciseItem->id]);
    }

    public function test_deleting_course_removes_all_lesson_files(): void
    {
        $lesson = $this->createLesson();
        $lesson->update([
            'audio_file' => 'lesson_audio/course-legacy.mp3',
            'media_files' => ['lesson_media/course-legacy.pdf'],
            'homework_files' => ['homework_files/course-legacy.docx'],
        ]);
        Storage::disk('public')->put($lesson->audio_file, 'audio');
        Storage::disk('public')->put($lesson->media_files[0], 'pdf');
        Storage::disk('public')->put($lesson->homework_files[0], 'docx');
        $exercise = $lesson->exercises()->create([
            'type' => 'dictation',
            'title' => 'Dictation',
            'position' => 1,
            'is_active' => true,
        ]);
        $exerciseItem = $exercise->items()->create([
            'prompt' => '',
            'answer' => 'Good morning.',
            'audio_path' => 'lesson-exercises/audio/morning.mp3',
            'position' => 1,
        ]);
        Storage::disk('public')->put($exerciseItem->audio_path, 'audio');

        $this->actingAs($this->admin)
            ->delete(route('admin.course.delete', $lesson->course))
            ->assertRedirect(route('admin.course.index'));

        Storage::disk('public')->assertMissing($exerciseItem->audio_path);
        Storage::disk('public')->assertMissing('lesson_audio/course-legacy.mp3');
        Storage::disk('public')->assertMissing('lesson_media/course-legacy.pdf');
        Storage::disk('public')->assertMissing('homework_files/course-legacy.docx');
        $this->assertDatabaseMissing('lesson_exercise_items', ['id' => $exerciseItem->id]);
    }

    private function createLesson(?Course $course = null): Lesson
    {
        $course ??= Course::create([
            'title' => 'English A1',
            'description' => 'Course description',
            'language_id' => Language::create(['name' => fake()->unique()->languageCode()])->id,
            'price' => 0,
            'is_published' => true,
        ]);

        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson '.fake()->unique()->numberBetween(1, 10000),
            'description' => 'Lesson description',
            'position' => $course->lessons()->count() + 1,
            'is_published' => true,
        ]);
    }

    private function createTextBlock(Lesson $lesson, string $title, int $position): LessonContentBlock
    {
        return $lesson->contentBlocks()->create([
            'type' => 'text',
            'title' => $title,
            'content' => '<p>'.$title.' content</p>',
            'position' => $position,
            'is_active' => true,
        ]);
    }
}

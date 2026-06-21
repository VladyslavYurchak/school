<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLessonContentBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_lesson_renders_only_active_blocks_in_saved_order(): void
    {
        [$course, $lesson] = $this->createFreeLesson();

        $lesson->contentBlocks()->createMany([
            [
                'type' => 'text',
                'title' => 'Second visible block',
                'content' => '<p>Second content</p>',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'type' => 'text',
                'title' => 'Hidden block',
                'content' => '<p>Secret content</p>',
                'position' => 1,
                'is_active' => false,
            ],
            [
                'type' => 'video',
                'title' => 'First visible block',
                'video_url' => 'https://www.youtube.com/embed/abc123',
                'position' => 1,
                'is_active' => true,
            ],
        ]);

        $html = $this->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertSee('First visible block')
            ->assertSee('Second visible block')
            ->assertSee('https://www.youtube.com/embed/abc123', false)
            ->assertDontSee('Hidden block')
            ->assertDontSee('Secret content')
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'Second visible block'),
            strpos($html, 'First visible block')
        );
    }

    public function test_public_lesson_renders_media_blocks_with_expected_controls(): void
    {
        [$course, $lesson] = $this->createFreeLesson();

        $lesson->contentBlocks()->createMany([
            [
                'type' => 'audio',
                'title' => 'Listening practice',
                'media_path' => 'lesson_blocks/audio/listening.mp3',
                'media_name' => 'listening.mp3',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'type' => 'image',
                'title' => 'Grammar scheme',
                'media_path' => 'lesson_blocks/image/scheme.webp',
                'media_name' => 'scheme.webp',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'type' => 'pdf',
                'title' => 'Workbook',
                'media_path' => 'lesson_blocks/pdf/workbook.pdf',
                'media_name' => 'workbook.pdf',
                'position' => 3,
                'is_active' => true,
            ],
        ]);

        $this->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertSee('lesson-material-block--audio', false)
            ->assertSee('lesson-material-block--image', false)
            ->assertSee('lesson-material-block--pdf', false)
            ->assertSee('lesson_blocks/audio/listening.mp3', false)
            ->assertSee('lesson_blocks/image/scheme.webp', false)
            ->assertSee('lesson_blocks/pdf/workbook.pdf', false)
            ->assertSee('Завантажити аудіо')
            ->assertSee('Відкрити PDF')
            ->assertSee('download', false);
    }

    public function test_test_and_homework_appear_after_content_blocks(): void
    {
        [$course, $lesson] = $this->createFreeLesson();
        $lesson->contentBlocks()->create([
            'type' => 'text',
            'title' => 'Lesson material marker',
            'content' => '<p>Main explanation</p>',
            'position' => 1,
            'is_active' => true,
        ]);
        $lesson->update(['homework_text' => '<p>Homework marker</p>']);
        $test = $lesson->tests()->create([
            'question' => 'Test marker',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);
        $test->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
            ['option_text' => 'C', 'is_correct' => false],
        ]);

        $html = $this->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertSee('Lesson material marker')
            ->assertSee('Test marker')
            ->assertSee('Homework marker')
            ->assertDontSee('<style>', false)
            ->getContent();

        $materialPosition = strpos($html, 'Lesson material marker');
        $testPosition = strpos($html, 'Test marker');
        $homeworkPosition = strpos($html, 'Homework marker');

        $this->assertLessThan($testPosition, $materialPosition);
        $this->assertLessThan($homeworkPosition, $testPosition);
    }

    private function createFreeLesson(): array
    {
        $language = Language::create(['name' => 'English']);
        $course = Course::create([
            'title' => 'English A1',
            'description' => 'Course description',
            'language_id' => $language->id,
            'price' => 0,
            'is_published' => true,
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson one',
            'description' => 'Lesson description',
            'position' => 1,
            'price' => 0,
            'is_published' => true,
        ]);

        return [$course, $lesson];
    }
}

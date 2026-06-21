<?php

namespace Tests\Feature;

use App\Models\Testing\Option;
use App\Models\Testing\Question;
use App\Models\Testing\Section;
use App\Models\Testing\Session as TestingSession;
use App\Models\Testing\Test as TestingTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Tests\TestCase;

class PublicTestingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_testing_walks_through_all_active_tests_before_result(): void
    {
        $a2 = $this->createPlacementTest('A2', 1);
        $b1 = $this->createPlacementTest('B1', 2);

        $this
            ->post(route('testing.start', 'en'))
            ->assertRedirect(route('testing.session.show', [
                'session' => TestingSession::first(),
                'step' => 1,
            ]));

        $session = TestingSession::with('attempts')->first();

        $this->assertCount(2, $session->attempts);

        $this
            ->get(route('testing.session.show', ['session' => $session, 'step' => 1]))
            ->assertOk()
            ->assertSee($a2['test']->title);

        $this
            ->post(route('testing.session.submit', $session), [
                'step' => 1,
                'answers' => [
                    $a2['question']->id => $a2['wrong_option']->id,
                ],
            ])
            ->assertRedirect(route('testing.session.show', [
                'session' => $session,
                'step' => 2,
            ]));

        $session->refresh();
        $this->assertSame('in_progress', $session->status);

        $this
            ->get(route('testing.session.show', ['session' => $session, 'step' => 2]))
            ->assertOk()
            ->assertSee($b1['test']->title);
    }

    public function test_public_testing_detects_highest_passed_level_without_stopping_on_lower_failed_level(): void
    {
        $levels = collect(['A2', 'B1', 'B2', 'C1'])
            ->mapWithKeys(fn (string $level, int $index) => [
                $level => $this->createPlacementTest($level, $index + 1),
            ]);

        $this->post(route('testing.start', 'en'));

        $session = TestingSession::firstOrFail();

        foreach ($levels->values() as $index => $levelData) {
            $selectedOption = $levelData['test']->title === 'A2 Test'
                ? $levelData['wrong_option']
                : $levelData['correct_option'];

            $this
                ->post(route('testing.session.submit', $session), [
                    'step' => $index + 1,
                    'answers' => [
                        $levelData['question']->id => $selectedOption->id,
                    ],
                ])
                ->assertRedirect();
        }

        $session->refresh();

        $this->assertSame('completed', $session->status);
        $this->assertSame('C1', $session->detected_level);
    }

    public function test_admin_can_upload_audio_file_for_testing_section(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $test = TestingTest::create([
            'title' => 'Listening Test',
            'slug' => 'en-listening-test',
            'language_code' => 'en',
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.testing.tests.sections.store', $test), [
                'title' => 'Listening',
                'type' => 'listening',
                'media_type' => 'none',
                'media_file' => UploadedFile::fake()->create('listening.mp3', 100, 'audio/mpeg'),
                'media_title' => 'Listening Part 1',
                'sort_order' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.testing.tests.sections.index', $test));

        $section = Section::firstOrFail();

        $this->assertSame('audio', $section->media_type);
        $this->assertStringStartsWith('testing_audio/', $section->media_url);
        Storage::disk('public')->assertExists($section->media_url);
    }

    public function test_public_testing_renders_section_audio_player(): void
    {
        $test = TestingTest::create([
            'title' => 'A1 Listening Test',
            'slug' => 'en-a1-listening-test',
            'language_code' => 'en',
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $section = Section::create([
            'test_id' => $test->id,
            'title' => 'Listening',
            'type' => 'listening',
            'media_type' => 'audio',
            'media_url' => 'testing_audio/listening.mp3',
            'media_title' => 'Listening Part 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $question = Question::create([
            'test_id' => $test->id,
            'section_id' => $section->id,
            'type' => 'single_choice',
            'question_text' => 'What is the speaker talking about?',
            'default_correct_points' => 1,
            'default_incorrect_points' => 0,
            'difficulty_level' => 'A1',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Option::create([
            'question_id' => $question->id,
            'option_text' => 'A class',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        $this->post(route('testing.start', 'en'));

        $session = TestingSession::firstOrFail();

        $this
            ->get(route('testing.session.show', ['session' => $session, 'step' => 1]))
            ->assertOk()
            ->assertSee('Listening Part 1')
            ->assertSee('<audio controls', false)
            ->assertSee('storage/testing_audio/listening.mp3', false);
    }

    public function test_result_does_not_show_inactive_sections_or_questions(): void
    {
        $test = TestingTest::create([
            'title' => 'A1 Mixed Test',
            'slug' => 'en-a1-mixed-test',
            'language_code' => 'en',
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $activeSection = Section::create([
            'test_id' => $test->id,
            'title' => 'Active Grammar',
            'type' => 'grammar',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $inactiveSection = Section::create([
            'test_id' => $test->id,
            'title' => 'Hidden Listening',
            'type' => 'listening',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $activeQuestion = Question::create([
            'test_id' => $test->id,
            'section_id' => $activeSection->id,
            'type' => 'single_choice',
            'question_text' => 'Choose the correct sentence.',
            'default_correct_points' => 1,
            'default_incorrect_points' => 0,
            'difficulty_level' => 'A1',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $hiddenQuestion = Question::create([
            'test_id' => $test->id,
            'section_id' => $inactiveSection->id,
            'type' => 'single_choice',
            'question_text' => 'Why were real conversations difficult for Daniel?',
            'default_correct_points' => 1,
            'default_incorrect_points' => 0,
            'difficulty_level' => 'A1',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $correctOption = Option::create([
            'question_id' => $activeQuestion->id,
            'option_text' => 'He is a student.',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        Option::create([
            'question_id' => $hiddenQuestion->id,
            'option_text' => 'He has only one class',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        $this->post(route('testing.start', 'en'));

        $session = TestingSession::firstOrFail();

        $this
            ->post(route('testing.session.submit', $session), [
                'step' => 1,
                'answers' => [
                    $activeQuestion->id => $correctOption->id,
                ],
            ])
            ->assertRedirect(route('testing.session.result', $session));

        $this
            ->get(route('testing.session.result', $session))
            ->assertOk()
            ->assertSee('Active Grammar')
            ->assertSee('Choose the correct sentence.')
            ->assertDontSee('Hidden Listening')
            ->assertDontSee('Why were real conversations difficult for Daniel?')
            ->assertDontSee('He has only one class');
    }

    public function test_public_testing_renders_reading_passage_as_section_text(): void
    {
        $test = TestingTest::create([
            'title' => 'A1 Reading Test',
            'slug' => 'en-a1-reading-test',
            'language_code' => 'en',
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $section = Section::create([
            'test_id' => $test->id,
            'title' => 'Reading A1',
            'description' => "Daniel is from Spain.\n\nHe studies English every morning.",
            'instruction_text' => 'Read the text and choose the correct answer.',
            'type' => 'reading',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $question = Question::create([
            'test_id' => $test->id,
            'section_id' => $section->id,
            'type' => 'single_choice',
            'question_text' => 'Where is Daniel from?',
            'default_correct_points' => 1,
            'default_incorrect_points' => 0,
            'difficulty_level' => 'A1',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Option::create([
            'question_id' => $question->id,
            'option_text' => 'Spain',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        $this->post(route('testing.start', 'en'));

        $session = TestingSession::firstOrFail();

        $this
            ->get(route('testing.session.show', ['session' => $session, 'step' => 1]))
            ->assertOk()
            ->assertSee('Read the text and choose the correct answer.')
            ->assertSee('class="testing-section-text mb-4"', false)
            ->assertSee('Daniel is from Spain.')
            ->assertSee('He studies English every morning.')
            ->assertSee('Where is Daniel from?');
    }

    private function createPlacementTest(string $level, int $sortOrder): array
    {
        $test = TestingTest::create([
            'title' => "{$level} Test",
            'slug' => "en-{$level}-test",
            'language_code' => 'en',
            'is_active' => true,
            'is_public' => true,
            'sort_order' => $sortOrder,
        ]);

        $section = Section::create([
            'test_id' => $test->id,
            'title' => "{$level} Grammar",
            'type' => 'grammar',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $question = Question::create([
            'test_id' => $test->id,
            'section_id' => $section->id,
            'type' => 'single_choice',
            'question_text' => "{$level} question",
            'default_correct_points' => 1,
            'default_incorrect_points' => 0,
            'difficulty_level' => $level,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $correctOption = Option::create([
            'question_id' => $question->id,
            'option_text' => 'Correct',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        $wrongOption = Option::create([
            'question_id' => $question->id,
            'option_text' => 'Wrong',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        return [
            'test' => $test,
            'section' => $section,
            'question' => $question,
            'correct_option' => $correctOption,
            'wrong_option' => $wrongOption,
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Testing\Option;
use App\Models\Testing\Question;
use App\Models\Testing\Section;
use App\Models\Testing\Session as TestingSession;
use App\Models\Testing\Test as TestingTest;
use App\Models\Testing\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
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

    public function test_public_testing_starts_only_public_tests_with_active_questions(): void
    {
        $public = $this->createPlacementTest('A1', 1);
        $private = $this->createPlacementTest('A2', 2);
        $private['test']->update(['is_public' => false]);

        $empty = TestingTest::create([
            'title' => 'Empty Public Test',
            'slug' => 'empty-public-test',
            'language_code' => 'en',
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 3,
        ]);

        Section::create([
            'test_id' => $empty->id,
            'title' => 'Empty section',
            'type' => 'grammar',
            'is_active' => true,
        ]);

        $this->post(route('testing.start', 'en'))->assertRedirect();

        $session = TestingSession::with('attempts')->firstOrFail();

        $this->assertCount(1, $session->attempts);
        $this->assertSame($public['test']->id, $session->attempts->first()->test_id);
    }

    public function test_homepage_shows_only_languages_with_available_public_tests(): void
    {
        $english = $this->createPlacementTest('A1', 1);
        $hiddenFrench = $this->createPlacementTest('A2', 2);
        $hiddenFrench['test']->update([
            'language_code' => 'fr',
            'is_public' => false,
        ]);

        $this
            ->get(route('index'))
            ->assertOk()
            ->assertSee('Англійська')
            ->assertDontSee('Французька')
            ->assertDontSee('Китайська');
    }

    public function test_homepage_explains_when_no_public_test_is_available(): void
    {
        $this
            ->get(route('index'))
            ->assertOk()
            ->assertSee('Нове тестування вже готується')
            ->assertDontSee('Англійська');
    }

    public function test_test_with_no_correct_option_is_not_offered_to_visitors(): void
    {
        $level = $this->createPlacementTest('A1', 1);
        $level['correct_option']->update(['is_correct' => false]);

        $this
            ->get(route('index'))
            ->assertOk()
            ->assertSee('Нове тестування вже готується')
            ->assertDontSee('Англійська');

        $this
            ->post(route('testing.start', 'en'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('testing_sessions', 0);
    }

    public function test_testing_session_uses_an_opaque_public_route_key(): void
    {
        $this->createPlacementTest('A1', 1);
        $this->post(route('testing.start', 'en'));

        $session = TestingSession::firstOrFail();

        $this->assertNotNull($session->public_token);
        $this->assertStringContainsString($session->public_token, route('testing.session.show', $session));
        $this->get("/testing/session/{$session->id}")->assertNotFound();
    }

    public function test_visitor_cannot_skip_a_testing_step_or_open_result_early(): void
    {
        $first = $this->createPlacementTest('A1', 1);
        $second = $this->createPlacementTest('A2', 2);

        $this->post(route('testing.start', 'en'));
        $session = TestingSession::firstOrFail();

        $this
            ->get(route('testing.session.show', ['session' => $session, 'step' => 2]))
            ->assertRedirect(route('testing.session.show', ['session' => $session, 'step' => 1]));

        $this
            ->post(route('testing.session.submit', $session), [
                'step' => 2,
                'answers' => [$second['question']->id => $second['correct_option']->id],
            ])
            ->assertRedirect(route('testing.session.show', ['session' => $session, 'step' => 1]));

        $this
            ->get(route('testing.session.result', $session))
            ->assertRedirect(route('testing.session.show', ['session' => $session, 'step' => 1]));

        $this->assertDatabaseMissing('testing_answers', [
            'question_id' => $first['question']->id,
        ]);
        $this->assertDatabaseMissing('testing_answers', [
            'question_id' => $second['question']->id,
        ]);
    }

    public function test_required_questions_are_validated_on_the_server(): void
    {
        $level = $this->createPlacementTest('A1', 1);
        $this->post(route('testing.start', 'en'));
        $session = TestingSession::firstOrFail();

        $this
            ->from(route('testing.session.show', ['session' => $session, 'step' => 1]))
            ->post(route('testing.session.submit', $session), ['step' => 1])
            ->assertSessionHasErrors("answers.{$level['question']->id}");

        $this->assertSame('in_progress', $session->fresh()->status);
        $this->assertDatabaseCount('testing_answers', 0);
    }

    public function test_optional_question_can_be_skipped(): void
    {
        $level = $this->createPlacementTest('A1', 1);
        $level['question']->update(['is_required' => false]);

        $this->post(route('testing.start', 'en'));
        $session = TestingSession::firstOrFail();

        $this
            ->post(route('testing.session.submit', $session), ['step' => 1])
            ->assertRedirect(route('testing.session.result', $session));

        $this->assertSame('completed', $session->fresh()->status);
    }

    public function test_lead_requires_contact_method_and_consent_after_completed_test(): void
    {
        $level = $this->createPlacementTest('A1', 1);
        $this->post(route('testing.start', 'en'));
        $session = TestingSession::firstOrFail();

        $this->post(route('testing.session.submit', $session), [
            'step' => 1,
            'answers' => [$level['question']->id => $level['correct_option']->id],
        ]);

        $this
            ->post(route('testing.session.lead.store', $session), ['name' => 'Visitor'])
            ->assertSessionHasErrors(['phone', 'email', 'telegram', 'contact_consent']);

        $this
            ->post(route('testing.session.lead.store', $session), [
                'name' => 'Visitor',
                'email' => 'visitor@example.com',
                'contact_consent' => 1,
            ])
            ->assertRedirect(route('testing.session.result', $session));

        $lead = Lead::firstOrFail();
        $this->assertSame('visitor@example.com', $lead->email);
        $this->assertTrue($lead->contact_consent);
    }

    public function test_answer_option_must_belong_to_the_current_question(): void
    {
        $first = $this->createPlacementTest('A1', 1);
        $other = $this->createPlacementTest('A2', 2);
        $this->post(route('testing.start', 'en'));
        $session = TestingSession::firstOrFail();

        $this
            ->post(route('testing.session.submit', $session), [
                'step' => 1,
                'answers' => [
                    $first['question']->id => $other['correct_option']->id,
                ],
            ])
            ->assertSessionHasErrors("answers.{$first['question']->id}");

        $this->assertDatabaseCount('testing_answers', 0);
    }

    public function test_unanswered_level_questions_count_toward_level_accuracy(): void
    {
        $level = $this->createPlacementTest('B2', 1);

        for ($index = 2; $index <= 5; $index++) {
            $question = Question::create([
                'test_id' => $level['test']->id,
                'section_id' => $level['section']->id,
                'type' => 'single_choice',
                'question_text' => "B2 question {$index}",
                'difficulty_level' => 'B2',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => $index,
            ]);

            Option::create([
                'question_id' => $question->id,
                'option_text' => 'Correct',
                'is_correct' => true,
            ]);
        }

        $this->post(route('testing.start', 'en'));
        $session = TestingSession::firstOrFail();

        $this->post(route('testing.session.submit', $session), [
            'step' => 1,
            'answers' => [
                $level['question']->id => $level['correct_option']->id,
            ],
        ]);

        $this->assertSame('A1', $session->fresh()->detected_level);
    }

    public function test_expired_timed_section_can_finish_without_required_answers(): void
    {
        $level = $this->createPlacementTest('A1', 1);
        $level['test']->update(['time_limit_minutes' => 10]);

        Carbon::setTestNow('2026-07-24 12:00:00');
        $this->post(route('testing.start', 'en'));
        $session = TestingSession::firstOrFail();

        Carbon::setTestNow('2026-07-24 12:11:00');

        $this
            ->post(route('testing.session.submit', $session), ['step' => 1])
            ->assertRedirect(route('testing.session.result', $session));

        $this->assertSame('completed', $session->fresh()->status);
        $this->assertDatabaseCount('testing_answers', 0);

        Carbon::setTestNow();
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

    public function test_correct_answers_are_hidden_when_test_review_is_disabled(): void
    {
        $level = $this->createPlacementTest('A1', 1);
        $level['test']->update(['show_result_immediately' => false]);

        $this->post(route('testing.start', 'en'));
        $session = TestingSession::firstOrFail();

        $this->post(route('testing.session.submit', $session), [
            'step' => 1,
            'answers' => [
                $level['question']->id => $level['correct_option']->id,
            ],
        ]);

        $this
            ->get(route('testing.session.result', $session))
            ->assertOk()
            ->assertSee('Ваш орієнтовний рівень')
            ->assertDontSee('Розбір відповідей')
            ->assertDontSee($level['question']->question_text);
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
            ->assertSee('class="testing-section-text"', false)
            ->assertSee('Daniel is from Spain.')
            ->assertSee('He studies English every morning.')
            ->assertSee('Where is Daniel from?');
    }

    public function test_test_intro_text_is_visible_on_the_first_step(): void
    {
        $level = $this->createPlacementTest('A1', 1);
        $level['test']->update([
            'intro_text' => "Welcome to the test.\nTake your time.",
        ]);

        $this->post(route('testing.start', 'en'));
        $session = TestingSession::firstOrFail();

        $this
            ->get(route('testing.session.show', ['session' => $session, 'step' => 1]))
            ->assertOk()
            ->assertSee('Welcome to the test.')
            ->assertSee('Take your time.')
            ->assertSee('class="testing-intro"', false);
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

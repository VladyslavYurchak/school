<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\Testing\Answer;
use App\Models\Testing\Attempt;
use App\Models\Testing\Option;
use App\Models\Testing\Question;
use App\Models\Testing\ResultRange;
use App\Models\Testing\Section;
use App\Models\Testing\Session;
use App\Models\Testing\Test as TestingTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOperationsPagesLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_operations_pages_use_unified_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Teacher::factory()->create([
            'first_name' => 'Olena',
            'last_name' => 'Teacher',
            'lesson_price' => 500,
            'group_lesson_price' => 700,
            'pair_lesson_price' => 600,
            'trial_lesson_price' => 300,
        ]);

        TestingTest::create([
            'title' => 'English A1',
            'slug' => 'english-a1',
            'language_code' => 'en',
            'weight' => 1,
            'max_score' => 20,
            'is_active' => true,
            'is_public' => true,
            'randomize_questions' => false,
            'show_result_immediately' => true,
            'sort_order' => 1,
        ]);

        Session::create([
            'language_code' => 'en',
            'status' => 'finished',
            'total_raw_score' => 18,
            'total_weighted_score' => 18,
            'max_weighted_score' => 20,
            'detected_level' => 'B1',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        foreach ([
            route('admin.calendar_teachers.teachers.index'),
            route('admin.information.index'),
            route('admin.teachers.index'),
            route('admin.testing.tests.index'),
            route('admin.testing.sessions.index'),
        ] as $url) {
            $response = $this
                ->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('class="admin-page"', false)
                ->assertSee('class="admin-hero"', false)
                ->assertSee('admin-panel', false)
                ->assertDontSee('<style>', false);

            $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
        }
    }

    public function test_calendar_teachers_keeps_teacher_filter_and_calendar_events_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create([
            'first_name' => 'Iryna',
            'last_name' => 'Calendar',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.calendar_teachers.teachers.index'))
            ->assertOk()
            ->assertSee('id="teacher-filter"', false)
            ->assertSee('value="'.$teacher->id.'"', false)
            ->assertSee(route('admin.calendar_teachers.teachers.events'), false)
            ->assertSee('calendar.refetchEvents()', false);
    }

    public function test_admin_testing_inner_pages_use_unified_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $test = TestingTest::create([
            'title' => 'English Placement',
            'slug' => 'english-placement',
            'language_code' => 'en',
            'weight' => 1,
            'max_score' => 20,
            'is_active' => true,
            'is_public' => true,
            'randomize_questions' => false,
            'show_result_immediately' => true,
            'sort_order' => 1,
        ]);

        $section = Section::create([
            'test_id' => $test->id,
            'title' => 'Grammar',
            'type' => 'grammar',
            'media_type' => 'none',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $question = Question::create([
            'test_id' => $test->id,
            'section_id' => $section->id,
            'type' => 'single_choice',
            'question_text' => 'Choose the correct answer.',
            'default_correct_points' => 1,
            'default_incorrect_points' => 0,
            'difficulty_level' => 'A1',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $option = Option::create([
            'question_id' => $question->id,
            'option_text' => 'Correct answer',
            'is_correct' => true,
            'points' => 1,
            'sort_order' => 1,
        ]);

        $range = ResultRange::create([
            'test_id' => $test->id,
            'title' => 'Beginner',
            'level_code' => 'A1',
            'min_score' => 0,
            'max_score' => 20,
        ]);

        $session = Session::create([
            'language_code' => 'en',
            'status' => 'finished',
            'total_raw_score' => 1,
            'total_weighted_score' => 1,
            'max_weighted_score' => 20,
            'detected_level' => 'A1',
            'result_range_id' => $range->id,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $attempt = Attempt::create([
            'session_id' => $session->id,
            'test_id' => $test->id,
            'status' => 'finished',
            'raw_score' => 1,
            'weighted_score' => 1,
            'max_score' => 20,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $option->id,
            'answer_text' => null,
            'is_correct' => true,
            'awarded_points' => 1,
        ]);

        foreach ([
            route('admin.testing.tests.create'),
            route('admin.testing.tests.edit', $test),
            route('admin.testing.tests.sections.index', $test),
            route('admin.testing.tests.sections.create', $test),
            route('admin.testing.sections.edit', $section),
            route('admin.testing.tests.questions.index', $test),
            route('admin.testing.tests.questions.create', $test),
            route('admin.testing.questions.edit', $question),
            route('admin.testing.questions.options.index', $question),
            route('admin.testing.questions.options.create', $question),
            route('admin.testing.options.edit', $option),
            route('admin.testing.tests.result-ranges.index', $test),
            route('admin.testing.tests.result-ranges.create', $test),
            route('admin.testing.result-ranges.edit', $range),
            route('admin.testing.sessions.show', $session),
        ] as $url) {
            $response = $this
                ->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('class="admin-page"', false)
                ->assertSee('admin-panel', false)
                ->assertDontSee('app-content', false)
                ->assertDontSee('btn btn-', false)
                ->assertDontSee('class="card', false)
                ->assertDontSee('<style>', false)
                ->assertDontSee('style="', false);

            $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
        }
    }
}

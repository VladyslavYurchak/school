<?php

namespace Tests\Feature;

use App\Models\Testing\Test as TestingTest;
use Database\Seeders\EnglishPlacementTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnglishPlacementTestSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_complete_public_placement_test(): void
    {
        $this->seed(EnglishPlacementTestSeeder::class);

        $test = TestingTest::query()
            ->with('sections.questions.options')
            ->where('slug', 'english-placement-2026-v1')
            ->firstOrFail();

        $questions = $test->sections->flatMap->questions;

        $this->assertTrue($test->is_active);
        $this->assertTrue($test->is_public);
        $this->assertSame('en', $test->language_code);
        $this->assertSame(6, $test->sections->count());
        $this->assertSame(50, $questions->count());
        $this->assertSame(50, $questions->pluck('question_text')->unique()->count());
        $this->assertSame('50.00', $test->max_score);

        foreach (['A1', 'A2', 'B1', 'B2', 'C1'] as $level) {
            $this->assertSame(
                10,
                $questions->where('difficulty_level', $level)->count(),
                "Expected exactly 10 questions for {$level}."
            );
        }

        foreach ($questions as $question) {
            $this->assertSame('single_choice', $question->type);
            $this->assertTrue($question->is_active);
            $this->assertTrue($question->is_required);
            $this->assertSame(4, $question->options->count());
            $this->assertSame(1, $question->options->where('is_correct', true)->count());
            $this->assertNotEmpty(
                $question->options->firstWhere('is_correct', true)?->explanation
            );
        }

        $this->assertTrue(
            TestingTest::query()
                ->publiclyAvailable()
                ->whereKey($test->id)
                ->exists()
        );
    }

    public function test_seeder_can_be_run_twice_without_creating_duplicates(): void
    {
        $this->seed(EnglishPlacementTestSeeder::class);
        $this->seed(EnglishPlacementTestSeeder::class);

        $this->assertDatabaseCount('testing_tests', 1);
        $this->assertDatabaseCount('testing_sections', 6);
        $this->assertDatabaseCount('testing_questions', 50);
        $this->assertDatabaseCount('testing_options', 200);
    }
}

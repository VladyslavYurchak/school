<?php

namespace Database\Factories;

use App\Models\LessonLog;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class LessonLogFactory extends Factory
{
    protected $model = LessonLog::class;

    public function definition(): array
    {
        $dateTime = Carbon::instance(
            fake()->dateTimeBetween('-1 month', 'now')
        );

        return [
            'lesson_id'  => null, // частіше будемо задавати явно через state
            'student_id' => null,
            'teacher_id' => null,
            'group_id'   => null,

            'lesson_type' => fake()->randomElement(['individual', 'group', 'pair', 'trial']),
            'date'        => $dateTime->toDateString(),
            'time'        => $dateTime->format('H:i:s'),
            'duration'    => fake()->randomElement([45, 60, 90]),
            'status'      => 'completed',
            'notes'       => fake()->optional()->sentence(),

            'teacher_rate_amount_at_charge' => fake()->optional()->randomFloat(2, 200, 800),
            'teacher_payout_basis'          => fake()->optional()->randomElement(['per_lesson', 'per_hour', 'per_student', 'custom']),
            'teacher_payout_amount'         => fake()->optional()->randomFloat(2, 200, 800),
            'charged_at'                    => fake()->optional()->dateTimeBetween($dateTime, 'now'),
        ];
    }

    /**
     * Привʼязати до конкретного planned lesson
     */
    public function withPlannedLesson(?PlannedLesson $lesson = null): self
    {
        return $this->state(function () use ($lesson) {
            $lesson = $lesson ?? PlannedLesson::factory()->create();

            return [
                'lesson_id'  => $lesson->id,
                'teacher_id' => $lesson->teacher_id,
                'student_id' => $lesson->student_id,
                'group_id'   => $lesson->group_id,
                'lesson_type'=> $lesson->lesson_type,
                'date'       => $lesson->start_date instanceof \DateTimeInterface
                    ? $lesson->start_date->format('Y-m-d')
                    : substr((string)$lesson->start_date, 0, 10),
            ];
        });
    }

    /**
     * Привʼязати до конкретного викладача
     */
    public function forTeacher(?Teacher $teacher = null): self
    {
        return $this->state(fn () => [
            'teacher_id' => $teacher?->id ?? Teacher::factory(),
        ]);
    }

    /**
     * Привʼязати до студента
     */
    public function forStudent(?Student $student = null): self
    {
        return $this->state(fn () => [
            'student_id' => $student?->id ?? Student::factory(),
        ]);
    }

    /**
     * Привʼязати до групи
     */
    public function forGroup(?Group $group = null): self
    {
        return $this->state(fn () => [
            'group_id' => $group?->id ?? Group::factory(),
        ]);
    }

    /**
     * Стейт: ще не нараховано (charged_at = null, суми пусті)
     */
    public function notCharged(): self
    {
        return $this->state(fn () => [
            'charged_at'                    => null,
            'teacher_rate_amount_at_charge' => null,
            'teacher_payout_basis'          => null,
            'teacher_payout_amount'         => null,
        ]);
    }
}

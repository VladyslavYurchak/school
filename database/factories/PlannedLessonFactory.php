<?php

namespace Database\Factories;

use App\Models\PlannedLesson;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PlannedLessonFactory extends Factory
{
    protected $model = PlannedLesson::class;

    public function definition(): array
    {
        $start = Carbon::instance(
            fake()->dateTimeBetween('-1 week', '+1 week')
        );

        // зробимо урок 60 хвилин за замовчуванням
        $end = $start->copy()->addHour();

        return [
            'title'       => fake()->sentence(3),
            'lesson_type' => fake()->randomElement(['individual', 'group', 'pair', 'trial']),

            'teacher_id'  => Teacher::factory(),
            'student_id'  => null,           // будемо задавати явно в тестах, коли треба
            'group_id'    => null,

            'start_date'  => $start,         // datetime
            'end_date'    => $end,           // nullable, але тут ставимо значення

            'status'      => 'planned',
            'initiator'   => fake()->randomElement(['student', 'teacher']),
            'notes'       => fake()->optional()->sentence(),
        ];
    }

    /**
     * Стейт: індивідуальний урок
     */
    public function individual(): self
    {
        return $this->state(fn () => [
            'lesson_type' => 'individual',
            'group_id'    => null,
        ]);
    }

    /**
     * Стейт: груповий урок
     */
    public function group(): self
    {
        return $this->state(fn () => [
            'lesson_type' => 'group',
            'student_id'  => null,
        ]);
    }

    /**
     * Стейт: парний урок
     */
    public function pair(): self
    {
        return $this->state(fn () => [
            'lesson_type' => 'pair',
        ]);
    }

    /**
     * Стейт: пробний урок
     */
    public function trial(): self
    {
        return $this->state(fn () => [
            'lesson_type' => 'trial',
        ]);
    }

    /**
     * Привʼязати студента
     */
    public function withStudent(?Student $student = null): self
    {
        return $this->state(fn () => [
            'student_id' => $student?->id ?? Student::factory(),
        ]);
    }

    /**
     * Привʼязати групу
     */
    public function withGroup(?Group $group = null): self
    {
        return $this->state(fn () => [
            'group_id' => $group?->id ?? Group::factory(),
        ]);
    }

    /**
     * Стейт: конкретний діапазон дат (для зручності в тестах)
     */
    public function atRange(string $start, ?string $end = null): self
    {
        return $this->state(fn () => [
            'start_date' => $start,                         // 'Y-m-d H:i:s' або просто 'Y-m-d'
            'end_date'   => $end ?? $start,
        ]);
    }
}

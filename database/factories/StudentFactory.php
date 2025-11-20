<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Group;
use App\Models\SubscriptionTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name'  => fake()->lastName(),
            'phone'      => fake()->optional()->phoneNumber(),
            'email'      => fake()->optional()->safeEmail(),
            'birth_date' => fake()->optional()->date(),

            'teacher_id' => Teacher::factory(),     // nullable, але за замовчуванням ставимо
            'group_id'   => null,                  // у фабриці краще за замовчуванням не привʼязувати

            'custom_lesson_price'       => fake()->optional()->randomFloat(2, 200, 800),
            'custom_group_lesson_price' => fake()->optional()->randomFloat(2, 200, 800),

            'remaining_lessons'        => 0,
            'remaining_group_lessons'  => 0,
            'is_active'                => true,
            'parent_contact'           => fake()->optional()->phoneNumber(),
            'start_date'               => fake()->optional()->date(),
            'total_lessons_attended'   => 0,
            'balance'                  => 0,
            'note'                     => fake()->optional()->sentence(),

            'subscription_id' => null, // за замовчуванням студент без абонемента
        ];
    }

    /**
     * Студент з привʼязаною групою
     */
    public function withGroup(): self
    {
        return $this->state(fn () => [
            'group_id' => Group::factory(),
        ]);
    }

    /**
     * Студент із шаблоном абонемента
     */
    public function withSubscriptionTemplate(): self
    {
        return $this->state(fn () => [
            'subscription_id' => SubscriptionTemplate::factory(),
        ]);
    }

    /**
     * Неактивний студент
     */
    public function inactive(): self
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    /**
     * Без викладача (teacher_id = null)
     */
    public function withoutTeacher(): self
    {
        return $this->state(fn () => [
            'teacher_id' => null,
        ]);
    }
}

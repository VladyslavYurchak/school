<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'birth_date' => $this->faker->dateTimeBetween('-20 years', '-7 years')->format('Y-m-d'),
            'teacher_id' => null,
            'group_id' => null,
            'custom_lesson_price' => null,
            'custom_group_lesson_price' => null,
            'remaining_lessons' => 0,
            'remaining_group_lessons' => 0,
            'is_active' => true,
            'parent_contact' => $this->faker->phoneNumber(),
            'start_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'total_lessons_attended' => 0,
            'balance' => $this->faker->randomFloat(2, 0, 500),
            'note' => $this->faker->optional()->text(100),
            'subscription_id' => null,
        ];
    }
}

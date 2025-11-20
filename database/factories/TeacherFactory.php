<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            'first_name' => fake()->firstName(),
            'last_name'  => fake()->lastName(),
            'email'      => fake()->safeEmail(),
            'phone'      => fake()->phoneNumber(),

            'lesson_price'        => fake()->randomFloat(2, 200, 600),
            'group_lesson_price'  => fake()->randomFloat(2, 200, 600),
            'pair_lesson_price'   => fake()->randomFloat(2, 200, 600),
            'trial_lesson_price'  => fake()->randomFloat(2, 100, 300),

            'note' => fake()->optional()->sentence(),

            'is_active' => true,
        ];
    }
}

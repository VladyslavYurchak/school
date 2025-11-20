<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => 'Група ' . fake()->unique()->word(),
            'type' => fake()->randomElement(['group', 'pair']),
            'teacher_id' => Teacher::factory(), // nullable, але для тестів краще мати вчителя
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Стейт тільки група (group)
     */
    public function group(): self
    {
        return $this->state(fn () => [
            'type' => 'group',
        ]);
    }

    /**
     * Стейт парна група (pair)
     */
    public function pair(): self
    {
        return $this->state(fn () => [
            'type' => 'pair',
        ]);
    }

    /**
     * Стейт без викладача
     */
    public function withoutTeacher(): self
    {
        return $this->state(fn () => [
            'teacher_id' => null,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\SubscriptionTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionTemplateFactory extends Factory
{
    protected $model = SubscriptionTemplate::class;

    public function definition(): array
    {
        return [
            'title'            => 'Тариф ' . fake()->word(),
            'type'             => fake()->randomElement(['individual', 'group', 'pair', 'trial']),
            'lessons_per_week' => fake()->numberBetween(1, 5),
            'price'            => fake()->numberBetween(1000, 6000),
            // якщо колонки description немає в БД — не чіпаємо її тут
        ];
    }
}

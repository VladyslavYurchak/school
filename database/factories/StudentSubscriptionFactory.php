<?php

namespace Database\Factories;

use App\Models\StudentSubscription;
use App\Models\Student;
use App\Models\SubscriptionTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StudentSubscriptionFactory extends Factory
{
    protected $model = StudentSubscription::class;

    public function definition(): array
    {
        // Випадкова дата в цьому році
        $start = Carbon::instance(fake()->dateTimeThisYear());

        // Для "subscription" зробимо start/end як початок/кінець місяця
        $startDate = $start->copy()->startOfMonth();
        $endDate   = $start->copy()->endOfMonth();

        return [
            'student_id' => Student::factory(),
            'subscription_template_id' => SubscriptionTemplate::factory(),

            'price' => fake()->numberBetween(1000, 6000),

            'type' => 'subscription', // за замовчуванням

            'start_date' => $startDate->toDateString(),
            'end_date'   => $endDate->toDateString(),
        ];
    }

    /**
     * Стейт для поразової оплати (single)
     */
    public function single(): self
    {
        return $this->state(function () {
            $date = Carbon::instance(fake()->dateTimeThisYear())->toDateString();

            return [
                'type'       => 'single',
                'start_date' => $date,
                'end_date'   => $date,
                'subscription_template_id' => null,
            ];
        });
    }
}

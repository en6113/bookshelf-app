<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $timings = [
            'over_due_date',
            'three_days_before',
            'on_due_date',
            'three_days_after',
            'other',
        ];

        return [
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\ReadingPlanReminder',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => User::factory()->create()->id,
            'data' => [
                'reading_plan_id' => ReadingPlan::factory()->create()->id,
                'timing' => $this->faker->randomElement($timings),
                'title' => $this->faker->sentence(),
                'body' => $this->faker->realText(50),
            ],
        ];
    }
}

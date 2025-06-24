<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_date' => $this->faker->date(),
            'days' => $this->faker->numberBetween(1, 90),
            'initial_balance' => $this->faker->numberBetween(100000, 5000000),
            'current_balance' => function (array $attributes) {
                return $attributes['initial_balance'];
            },
            'bet_type' => $this->faker->randomElement(['manual', 'auto_heatmap', 'auto_streak', 'auto_rebound']),
            'status' => $this->faker->randomElement(['waiting', 'active', 'running', 'paused', 'finished', 'completed']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }

    public function withBalance($balance)
    {
        return $this->state(function (array $attributes) use ($balance) {
            return [
                'initial_balance' => $balance,
                'current_balance' => $balance,
            ];
        });
    }
}

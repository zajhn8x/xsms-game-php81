<?php

namespace Database\Factories;

use App\Models\CampaignBet;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignBetFactory extends Factory
{
    protected $model = CampaignBet::class;

    public function definition()
    {
        $points = $this->faker->numberBetween(1, 50);

        return [
            'campaign_id' => Campaign::factory(),
            'lo_number' => str_pad($this->faker->numberBetween(0, 99), 2, '0', STR_PAD_LEFT),
            'points' => $points,
            'amount' => $points * 23, // Standard calculation
            'win_amount' => 0,
            'bet_date' => $this->faker->date(),
            'is_win' => false,
            'status' => $this->faker->randomElement(['pending', 'completed']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'is_win' => false,
                'win_amount' => 0,
            ];
        });
    }

    public function winning()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'is_win' => true,
                'win_amount' => $attributes['amount'] * 80, // Standard payout 1:80
            ];
        });
    }

    public function losing()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'is_win' => false,
                'win_amount' => 0,
            ];
        });
    }

    public function withAmount($amount)
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'amount' => $amount,
                'points' => $amount / 23, // Reverse calculation
            ];
        });
    }

    public function forDate($date)
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'bet_date' => $date,
            ];
        });
    }
}

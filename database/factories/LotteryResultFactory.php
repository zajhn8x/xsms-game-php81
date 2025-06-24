<?php

namespace Database\Factories;

use App\Models\LotteryResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class LotteryResultFactory extends Factory
{
    protected $model = LotteryResult::class;

    public function definition()
    {
        // Generate random lottery numbers (lo)
        $loNumbers = [];
        for ($i = 0; $i < 27; $i++) {
            $loNumbers[] = str_pad($this->faker->numberBetween(0, 99), 2, '0', STR_PAD_LEFT);
        }

        return [
            'draw_date' => $this->faker->date(),
            'result_time' => $this->faker->time(),
            'giai_8' => str_pad($this->faker->numberBetween(0, 99), 2, '0', STR_PAD_LEFT),
            'giai_7' => str_pad($this->faker->numberBetween(100, 999), 3, '0', STR_PAD_LEFT),
            'giai_6_1' => str_pad($this->faker->numberBetween(1000, 9999), 4, '0', STR_PAD_LEFT),
            'giai_6_2' => str_pad($this->faker->numberBetween(1000, 9999), 4, '0', STR_PAD_LEFT),
            'giai_6_3' => str_pad($this->faker->numberBetween(1000, 9999), 4, '0', STR_PAD_LEFT),
            'giai_5_1' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_5_2' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_5_3' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_5_4' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_5_5' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_5_6' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_4_1' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_4_2' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_4_3' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_4_4' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_3_1' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_3_2' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_2' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_1' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'giai_db' => str_pad($this->faker->numberBetween(10000, 99999), 5, '0', STR_PAD_LEFT),
            'lo_array' => $loNumbers,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forDate($date)
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'draw_date' => $date,
            ];
        });
    }

    public function withLoNumbers(array $loNumbers)
    {
        return $this->state(function (array $attributes) use ($loNumbers) {
            return [
                'lo_array' => $loNumbers,
            ];
        });
    }

    public function withSpecificNumbers($giai8, $giai7, $giaiDB)
    {
        return $this->state(function (array $attributes) use ($giai8, $giai7, $giaiDB) {
            // Extract lo numbers from these prizes
            $loNumbers = [];

            // From giai 8 (2 digits)
            $loNumbers[] = str_pad($giai8, 2, '0', STR_PAD_LEFT);

            // From giai 7 (last 2 digits)
            $loNumbers[] = substr(str_pad($giai7, 3, '0', STR_PAD_LEFT), -2);

            // From giai DB (last 2 digits)
            $loNumbers[] = substr(str_pad($giaiDB, 5, '0', STR_PAD_LEFT), -2);

            return [
                'giai_8' => str_pad($giai8, 2, '0', STR_PAD_LEFT),
                'giai_7' => str_pad($giai7, 3, '0', STR_PAD_LEFT),
                'giai_db' => str_pad($giaiDB, 5, '0', STR_PAD_LEFT),
                'lo_array' => array_unique($loNumbers),
            ];
        });
    }
}

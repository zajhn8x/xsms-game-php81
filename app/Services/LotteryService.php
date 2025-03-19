<?php

namespace App\Services;

class LotteryService
{
    public function getResults()
    {
        return [
            (object)[
                'date' => '2024-01-20',
                'special_prize' => '12345',
                'first_prize' => '67890',
                'second_prize' => '11111'
            ],
            (object)[
                'date' => '2024-01-19',
                'special_prize' => '54321',
                'first_prize' => '09876',
                'second_prize' => '22222'
            ]
        ];
    }
}

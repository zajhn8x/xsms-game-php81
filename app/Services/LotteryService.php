
<?php

namespace App\Services;

class LotteryService
{
    public function getResults()
    {
        // Add lottery results logic here
        return (object)[
            'special_prize' => '12345',
            'first_prize' => '67890',
            'second_prize' => ['11111', '22222'],
            'third_prize' => ['33333', '44444', '55555'],
            'fourth_prize' => ['66666', '77777', '88888', '99999'],
            'fifth_prize' => ['12121', '23232', '34343'],
            'sixth_prize' => ['45454', '56565', '67676'],
            'seventh_prize' => ['78787', '89898', '90909']
        ];
    }
}

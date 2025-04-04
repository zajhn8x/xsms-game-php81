<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\LotteryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LotteryResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_lottery_result()
    {
        $data = [
            'draw_date' => '2005-10-01',
            'prizes' => [
                'special' => '34584',
                'prize1' => '16876',
                'prize2_1' => '34885'
            ],
            'lo_array' => ['34', '84', '76', '85']
        ];

        $result = LotteryResult::create($data);

        $this->assertInstanceOf(LotteryResult::class, $result);
        $this->assertEquals($data['draw_date'], $result->draw_date->format('Y-m-d'));
        $this->assertEquals($data['prizes'], $result->prizes);
        $this->assertEquals($data['lo_array'], $result->lo_array);
    }
}

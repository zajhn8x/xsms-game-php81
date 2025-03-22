
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\LotteryResultIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LotteryResultIndexTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_lottery_result_index()
    {
        $data = [
            'draw_date' => '2005-10-01',
            'position' => 'special',
            'value' => '34584'
        ];

        $index = LotteryResultIndex::create($data);

        $this->assertInstanceOf(LotteryResultIndex::class, $index);
        $this->assertEquals($data['draw_date'], $index->draw_date->format('Y-m-d'));
        $this->assertEquals($data['position'], $index->position);
        $this->assertEquals($data['value'], $index->value);
    }
}

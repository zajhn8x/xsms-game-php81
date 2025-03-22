
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
        $positions = config('xsmb.positions');
        $testPosition = $positions['special'][0]; // Using first special position
        
        $data = [
            'draw_date' => '2005-10-01',
            'position' => $testPosition,
            'value' => '34584'
        ];

        $index = LotteryResultIndex::create($data);

        $this->assertInstanceOf(LotteryResultIndex::class, $index);
        $this->assertEquals($data['draw_date'], $index->draw_date->format('Y-m-d'));
        $this->assertEquals($data['position'], $index->position);
        $this->assertEquals($data['value'], $index->value);
    }

    public function test_validates_position_from_config()
    {
        $positions = collect(config('xsmb.positions'))->flatten()->toArray();
        
        $data = [
            'draw_date' => '2005-10-01',
            'position' => 'invalid_position',
            'value' => '12345'
        ];

        $this->expectException(\Illuminate\Database\QueryException::class);
        LotteryResultIndex::create($data);
    }
}


<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LotteryFormulaService;
use App\Models\LotteryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LotteryFormulaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LotteryFormulaService::class);
    }

    public function test_can_calculate_formula_results()
    {
        // Create test data
        LotteryResult::create([
            'draw_date' => '2024-03-20',
            'prizes' => [
                'special' => '12345',
                'prize1' => '67890'
            ],
            'lo_array' => ['45', '90']
        ]);

        $result = $this->service->calculateFormulaResults('2024-03-20');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('special', $result);
        $this->assertArrayHasKey('prize1', $result);
    }
}


<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\StatisticsService;
use App\Models\LotteryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StatisticsService::class);
    }

    public function test_can_calculate_lo_frequency()
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

        $stats = $this->service->calculateLoFrequency('2024-03-20', '2024-03-20');
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('45', $stats);
        $this->assertArrayHasKey('90', $stats);
    }
}

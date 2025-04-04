<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LotteryResultsService;
use App\Models\LotteryResult;
use App\Models\LotteryResultIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class LotteryResultServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LotteryResultsService::class);
    }

    public function test_can_create_result_with_indexes()
    {
        $data = [
            'draw_date' => '2024-03-20',
            'prizes' => [
                'special' => '12345',
                'prize1' => '67890'
            ]
        ];

        $result = $this->service->createResult($data);

        $this->assertInstanceOf(LotteryResult::class, $result);
        $this->assertEquals($data['draw_date'], $result->draw_date->format('Y-m-d'));

        // Check indexes were created
        $this->assertDatabaseHas('lottery_result_indexes', [
            'draw_date' => $data['draw_date'],
            'position' => config('xsmb.positions.special')[0],
            'value' => '12345'
        ]);
    }

    public function test_can_get_results_by_date_range()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $results = $this->service->getResultsByDateRange($startDate, $endDate);

        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(LotteryResult::class, $result);
            $this->assertTrue(
                $result->draw_date->between($startDate, $endDate)
            );
        }
    }
}

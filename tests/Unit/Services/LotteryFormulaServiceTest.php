<?php

namespace Tests\Unit\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;
use App\Services\LotteryFormulaService;
use App\Models\LotteryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\LotteryFormula; // Assuming this model exists

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

    /**
     * Test xử lý khi không có kết quả xổ số
     */
    public function test_handle_no_lottery_result()
    {
        $cauLo = LotteryFormula::factory()->create();
        $result = $this->service->calculateResults($cauLo->id, '2024-03-21');
        $this->assertNull($result);
    }

    /**
     * Test xử lý khi cầu lô không tồn tại
     */
    public function test_handle_invalid_cau_lo()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->service->calculateResults(999, '2024-03-21');
    }

    public function test_can_save_processed_results()
    {
    }
}

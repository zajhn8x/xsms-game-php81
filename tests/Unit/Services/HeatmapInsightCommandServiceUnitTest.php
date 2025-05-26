<?php

namespace Tests\Unit\Services;

use App\Services\Commands\HeatmapInsightCommandService;
use PHPUnit\Framework\TestCase;

class HeatmapInsightCommandServiceUnitTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new HeatmapInsightCommandService();
    }

    public function test_isStop()
    {
        $this->assertTrue($this->service->isStop(['hit' => false]));
        $this->assertTrue($this->service->isStop(['hit' => null]));
        $this->assertFalse($this->service->isStop(['hit' => true]));
        echo "PASS: test_isStop\n";
    }

    public function test_findPrevStreak()
    {
        $heatmap = [
            '2025-05-18' => ['data' => [['id' => 1, 'streak' => 5]]],
            '2025-05-19' => ['data' => [['id' => 1, 'streak' => 1]]],
            '2025-05-20' => ['data' => [['id' => 1, 'streak' => 1]]],
        ];
        $dates = array_keys($heatmap);
        list($prevDate, $prevStreak, $prevIndex) = $this->service->findPrevStreak(1, $dates, $heatmap, 2);
        $this->assertEquals('2025-05-18', $prevDate);
        $this->assertEquals(5, $prevStreak);
        echo "PASS: test_findPrevStreak, ID: 1, prevDate: $prevDate, prevStreak: $prevStreak\n";
    }

    public function test_countStopDays()
    {
        $heatmap = [
            '2025-05-18' => ['data' => [['id' => 1, 'hit' => true]]],
            '2025-05-19' => ['data' => [['id' => 1, 'hit' => false]]],
            '2025-05-20' => ['data' => [['id' => 1, 'hit' => false]]],
        ];
        $dates = array_keys($heatmap);
        $stopDays = $this->service->countStopDays(1, $dates, $heatmap, 2, 0);
        $this->assertEquals(2, $stopDays);
        echo "PASS: test_countStopDays, ID: 1, stopDays: $stopDays\n";
    }

    public function test_checkTypeLongRun()
    {
        $this->assertTrue($this->service->checkTypeLongRun(6));
        $this->assertFalse($this->service->checkTypeLongRun(5));
        echo "PASS: test_checkTypeLongRun\n";
    }

    public function test_checkTypeLongRunStop()
    {
        $history = [1 => ['is_long_run' => true]];
        $this->assertTrue($this->service->checkTypeLongRunStop($history, 1, false));
        $this->assertFalse($this->service->checkTypeLongRunStop($history, 1, true));
        echo "PASS: test_checkTypeLongRunStop\n";
    }

    public function test_checkTypeRebound()
    {
        $heatmap = [
            '2025-05-18' => ['data' => [['id' => 1, 'streak' => 5, 'hit' => true]]],
            '2025-05-19' => ['data' => [['id' => 1, 'streak' => 1, 'hit' => false]]],
            '2025-05-20' => ['data' => [['id' => 1, 'streak' => 4, 'hit' => false]]],
        ];
        $dates = array_keys($heatmap);
        $result = $this->service->checkTypeRebound(1, $dates, $heatmap, 2);
        $this->assertIsArray($result);
        $this->assertEquals(5, $result['prev_streak']);
        $this->assertEquals(1, $result['stop_days']);
        echo "PASS: test_checkTypeRebound, ID: 1, prev_streak: {$result['prev_streak']}, stop_days: {$result['stop_days']}\n";
    }

    // Các hàm createInsight thường chỉ cần test gọi không lỗi, hoặc test với DB thực tế (integration test)
}

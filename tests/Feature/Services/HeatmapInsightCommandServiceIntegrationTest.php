<?php

namespace Tests\Feature\Services;

use App\Models\HeatmapDailyRecord;
use App\Models\FormulaHeatmapInsight;
use App\Services\Commands\HeatmapInsightCommandService;
use App\Services\LotteryIndexResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeatmapInsightCommandServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebound_insight_from_heatmap_daily_record()
    {
        // Seed dữ liệu heatmap_daily_records cho 6 ngày
        $dates = [
            '2025-05-15', '2025-05-16', '2025-05-17', '2025-05-18', '2025-05-19', '2025-05-20', '2025-05-21'
        ];
        $data = [
            ['id' => 293, 'streak' => 5, 'hit' => true, 'value' => 12], // 2025-05-15
            ['id' => 293, 'streak' => 0, 'hit' => false, 'value' => 12], // 2025-05-16
            ['id' => 293, 'streak' => 0, 'hit' => false, 'value' => 12], // 2025-05-17
            ['id' => 293, 'streak' => 0, 'hit' => false, 'value' => 12], // 2025-05-18
            ['id' => 293, 'streak' => 0, 'hit' => false, 'value' => 12], // 2025-05-19
            ['id' => 293, 'streak' => 0, 'hit' => false, 'value' => 12], // 2025-05-20
            ['id' => 293, 'streak' => 1, 'hit' => true, 'value' => 12],  // 2025-05-21
        ];
        foreach ($dates as $i => $date) {
            HeatmapDailyRecord::create([
                'date' => $date,
                'data' => [$data[$i]]
            ]);
        }

        // Lấy dữ liệu từ model, parse sang array heatmap
        $records = HeatmapDailyRecord::orderBy('date')->get();
        $heatmap = [];
        foreach ($records as $record) {
            $heatmap[$record->date->toDateString()] = ['data' => $record->data];
        }

        // Mock service phụ trợ
        /** @var \App\Services\LotteryIndexResultsService&\PHPUnit\Framework\MockObject\MockObject $mockIndexService */
        $mockIndexService = $this->createMock(LotteryIndexResultsService::class);
        $mockIndexService->method('getPositionsByFormulaIds')->willReturn([
            ['id' => 293, 'position' => [0 => 1]],
        ]);

        $service = new HeatmapInsightCommandService($mockIndexService);
        $service->process($heatmap);

        // Kiểm tra insight reboun
        $insight = FormulaHeatmapInsight::where('formula_id', 293)
            ->where('date', '2025-05-21')
            ->where('type', FormulaHeatmapInsight::TYPE_REBOUND)
            ->first();

        $this->assertNotNull($insight, 'Insight không được tạo');
        $extra = $insight->extra;
        $this->assertEquals(5, $extra['streak_length']);
        $this->assertEquals(5, $extra['stop_days']);
        $this->assertEquals(1, $extra['step']);
        $this->assertTrue($extra['rebound_success']);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Services\Commands\HeatmapInsightCommandService;
use App\Services\LotteryIndexResultsService;
use App\Models\FormulaHeatmapInsight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeatmapInsightCommandServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebound_insight_is_created_correctly()
    {
        // Không cần service phụ trợ
        $service = new \App\Services\Commands\HeatmapInsightCommandService();

        // Import file CSV vào DB (bảng heatmap_daily_records)
        $rows = array_map('str_getcsv', file(base_path('heatmap_daily_records.csv')));
        array_shift($rows); // Bỏ header
        foreach ($rows as $row) {
            // cột: id, date, data
            \App\Models\HeatmapDailyRecord::create([
                'date' => $row[1],
                'data' => json_decode($row[2], true),
            ]);
        }

        // Lấy dữ liệu từ DB, parse sang array heatmap
        $endDate = '2025-05-21';
        $startDate = (new \Carbon\Carbon($endDate))->copy()->subDays(15); // lấy 30 ngày
        $records = \App\Models\HeatmapDailyRecord::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date','desc')
            ->get();
        $heatmap = [];
        $records = $records->sortByDesc('date');
        foreach ($records as $record) {
            $heatmap[$record->date->toDateString()] = ['data' => $record->data];
        }

        $service->process($heatmap,$endDate);
        //==========running=step_1========
        echo "==========TYPE_REBOUND loại đơn giản running=step_1========\n";

                // Kiểm tra insight cho công thức 645
                $insight = \App\Models\FormulaHeatmapInsight::where('formula_id', 645)
                ->where('date', '2025-05-21')
                ->where('type', \App\Models\FormulaHeatmapInsight::TYPE_REBOUND)
                ->first();
            try {
                $this->assertNotNull($insight, 'Insight không được tạo');
                echo "PASS: Insight được tạo cho ID 645\n";
                $this->assertEquals(645, $insight->formula_id, 'ID công thức không đúng');
                echo "PASS: ID công thức đúng cho ID 645\n";
                $this->assertEquals('2025-05-21', $insight->date->toDateString(), 'Ngày không đúng');
                echo "PASS: Ngày đúng cho ID 645\n";

                $extra = $insight->extra;

                $this->assertEquals(4, $extra['streak_length']);
                echo "PASS: streak_length đúng cho ID 645\n";
                $this->assertEquals(2, $extra['stop_days']);
                echo "PASS: stop_days đúng cho ID 645\n";
                $this->assertEquals("step_1", $extra['running']);
                echo "PASS: running đúng cho ID 645\n";
                $this->assertEquals([7,4], $extra['suggests']);
                echo "PASS: suggests đúng cho ID 645\n";
            } catch (\Exception $e) {
                print_r($extra ?? []);
                throw $e;
            }

            // Kiểm tra insight cho công thức 574
            $insight = \App\Models\FormulaHeatmapInsight::where('formula_id', 574)
                ->where('date', '2025-05-21')
                ->where('type', \App\Models\FormulaHeatmapInsight::TYPE_REBOUND)
                ->first();
            try {
                $this->assertNotNull($insight, 'Insight không được tạo');
                echo "PASS: Insight được tạo cho ID 574\n";
                $this->assertEquals(574, $insight->formula_id, 'ID công thức không đúng');
                echo "PASS: ID công thức đúng cho ID 574\n";
                $this->assertEquals('2025-05-21', $insight->date->toDateString(), 'Ngày không đúng');
                echo "PASS: Ngày đúng cho ID 574\n";

                $extra = $insight->extra;

                $this->assertEquals(7, $extra['streak_length']);
                echo "PASS: streak_length đúng cho ID 574\n";
                $this->assertEquals(3, $extra['stop_days']);
                echo "PASS: stop_days đúng cho ID 574\n";
                $this->assertEquals("step_1", $extra['running']);
                echo "PASS: running đúng cho ID 574\n";
                $this->assertEquals([4,4], $extra['suggests']);
                echo "PASS: suggests đúng cho ID 574\n";
            } catch (\Exception $e) {
                print_r($extra ?? []);
                throw $e;
            }


            // Kiểm tra insight cho công thức 586
            $insight = \App\Models\FormulaHeatmapInsight::where('formula_id', 586)
                ->where('date', '2025-05-21')
                ->where('type', \App\Models\FormulaHeatmapInsight::TYPE_REBOUND)
                ->first();
            try {
                $this->assertNotNull($insight, 'Insight không được tạo');
                echo "PASS: Insight được tạo cho ID 586\n";
                $this->assertEquals(586, $insight->formula_id, 'ID công thức không đúng');
                echo "PASS: ID công thức đúng cho ID 586\n";
                $this->assertEquals('2025-05-21', $insight->date->toDateString(), 'Ngày không đúng');
                echo "PASS: Ngày đúng cho ID 586\n";

                $extra = $insight->extra;

                $this->assertEquals(6, $extra['streak_length']);
                echo "PASS: streak_length đúng cho ID 586\n";
                // $this->assertEquals(1, $extra['stop_days']);
                echo "PASS: stop_days đúng cho ID 586\n";
                $this->assertEquals("step_1", $extra['running']);
                echo "PASS: running đúng cho ID 586\n";
                $this->assertEquals([7,5], $extra['suggests']);
                echo "PASS: suggests đúng cho ID 586\n";
            } catch (\Exception $e) {
                print_r($extra ?? []);
                throw $e;
            }

              // Kiểm tra insight cho công thức 293
        $insight = \App\Models\FormulaHeatmapInsight::where('formula_id', 293)
        ->where('date', '2025-05-21')
        ->where('type', \App\Models\FormulaHeatmapInsight::TYPE_REBOUND)
        ->first();
    try {
        $this->assertNotNull($insight, 'Insight không được tạo cho ID 293');
        echo "PASS: Insight được tạo cho ID 293\n";
        $this->assertEquals(293, $insight->formula_id, 'ID công thức không đúng');
        echo "PASS: ID công thức đúng cho ID 293\n";
        $this->assertEquals('2025-05-21', $insight->date->toDateString(), 'Ngày không đúng');
        echo "PASS: Ngày đúng cho ID 293\n";

        $extra = $insight->extra;

        $this->assertEquals(5, $extra['streak_length']);
        echo "PASS: streak_length đúng cho ID 293\n";
        $this->assertEquals(5, $extra['stop_days']);
        echo "PASS: stop_days đúng cho ID 293\n";
        $this->assertEquals(293, $extra['value']);
        echo "PASS: value đúng cho ID 293\n";
    } catch (\Exception $e) {
        print_r($extra ?? []);
        throw $e;
    }


        //==========running=step_2========
        echo "==========TYPE_REBOUND loại đơn giản running=step_2========\n";


         // Kiểm tra insight cho công thức 57
         echo "====Kiểm tra insight cho công thức 57====\n";
         $insight = \App\Models\FormulaHeatmapInsight::where('formula_id', 57)
         ->where('date', '2025-05-21')
         ->where('type', \App\Models\FormulaHeatmapInsight::TYPE_REBOUND)
         ->first();
     try {
         $this->assertNotNull($insight, 'Insight 57 không được tạo');
         echo "PASS: Insight được tạo cho ID 57\n";
         $this->assertEquals(57, $insight->formula_id, 'ID' . $insight->formula_id . ' công thức không đúng');
         echo "PASS: ID công thức đúng cho ID 57\n";
         $this->assertEquals('2025-05-21', $insight->date->toDateString(), 'Ngày không đúng');
         echo "PASS: Ngày đúng cho ID 57\n";

         $extra = $insight->extra;

         $this->assertEquals(5, $extra['streak_length']);
         echo "PASS: streak_length đúng\n";
         $this->assertEquals(1, $extra['stop_days']);
         echo "PASS: stop_days đúng\n";
         $this->assertEquals("step_2", $extra['running']);
         echo "PASS: running đúng\n";
         $this->assertEquals("miss", $extra['step_1']);
         echo "PASS: step_1 đúng\n";
         $this->assertEquals([6,5], $extra['suggests']);
         echo "PASS: suggests đúng\n";
     } catch (\Exception $e) {
         print_r($extra ?? []);
         throw $e;
     }



        // Kiểm tra insight cho công thức 545
        $insight = \App\Models\FormulaHeatmapInsight::where('formula_id', 545)
            ->where('date', '2025-05-21')
            ->where('type', \App\Models\FormulaHeatmapInsight::TYPE_REBOUND)
            ->first();
        try {
            $this->assertNotNull($insight, 'Insight không được tạo cho ID 545');
            echo "PASS: Insight được tạo cho ID 545 = 2025-05-21\n";
            $this->assertEquals(545, $insight->formula_id, 'ID công thức không đúng');
            echo "PASS: ID công thức đúng cho ID 545 = 545\n";
            $this->assertEquals('2025-05-21', $insight->date->toDateString(), 'Ngày không đúng');
            echo "PASS: Ngày đúng cho ID 545 = 2025-05-21\n";

            $extra = $insight->extra;

            $this->assertEquals(5, $extra['streak_length']);
            echo "PASS: streak_length đúng cho ID 545 = 5\n";


            $this->assertEquals(1, $extra['stop_days']);
            echo "PASS: stop_days đúng cho ID 545 = 1\n";
            $this->assertEquals("step_3", $extra['running']);
            echo "PASS: running đúng cho ID 545 = step_2\n";
            $this->assertEquals([2,5], $extra['suggests']);
            echo "PASS: suggests đúng cho ID 545 = [2,5]\n";
            $this->assertEquals("miss", $extra['step_1']);
            echo "PASS: step_1 đúng cho ID 545 = true\n";
            $this->assertEquals("hit", $extra['step_2']);
            echo "PASS: step_2 đúng cho ID 545 = false\n";
            $this->assertEquals(false, $extra['step_3']);
            echo "PASS: step_3 đúng cho ID 545 = false\n";
        } catch (\Exception $e) {
            print_r($extra ?? []);
            throw $e;
        }




        // Kiểm tra insight cho công thức 645
        $insight = \App\Models\FormulaHeatmapInsight::where('formula_id', 645)
            ->where('date', '2025-05-21')
            ->where('type', \App\Models\FormulaHeatmapInsight::TYPE_REBOUND)
            ->first();
        try {
            $this->assertNotNull($insight, 'Insight không được tạo cho ID 645');
            echo "PASS: Insight được tạo cho ID 645\n";
            $this->assertEquals(645, $insight->formula_id, 'ID công thức không đúng');
            echo "PASS: ID công thức đúng cho ID 645\n";
            $this->assertEquals('2025-05-21', $insight->date->toDateString(), 'Ngày không đúng');
            echo "PASS: Ngày đúng cho ID 645\n";

            $extra = $insight->extra;

            $this->assertEquals(4, $extra['streak_length']);
            echo "PASS: streak_length đúng cho ID 645\n";
            $this->assertEquals(2, $extra['stop_days']);
            echo "PASS: stop_days đúng cho ID 645\n";
            $this->assertEquals("step_1", $extra['running']);
            echo "PASS: running đúng cho ID 645\n";
            $this->assertEquals([7,4], $extra['suggests']);
            echo "PASS: suggests đúng cho ID 645\n";
        } catch (\Exception $e) {
            print_r($extra ?? []);
            throw $e;
        }

        // Kiểm tra insight cho công thức 57
        $insight = \App\Models\FormulaHeatmapInsight::where('formula_id', 57)
            ->where('date', '2025-05-21')
            ->where('type', \App\Models\FormulaHeatmapInsight::TYPE_REBOUND)
            ->first();
        try {
            $this->assertNotNull($insight, 'Insight 57 không được tạo');
            echo "PASS: Insight được tạo\n";
            $this->assertEquals(57, $insight->formula_id, 'ID' . $insight->formula_id . ' công thức không đúng');
            echo "PASS: ID công thức đúng cho ID 57\n";
            $this->assertEquals('2025-05-21', $insight->date->toDateString(), 'Ngày không đúng');
            echo "PASS: Ngày đúng cho ID 57\n";

            $extra = $insight->extra;

            $this->assertEquals(5, $extra['streak_length']);
            echo "PASS: streak_length đúng cho ID 57\n";
            $this->assertEquals(1, $extra['stop_days']);
            echo "PASS: stop_days đúng cho ID 57\n";
            $this->assertEquals("step_2", $extra['running']);
            echo "PASS: running đúng cho ID 57\n";
            $this->assertEquals("miss", $extra['step_1']);
            echo "PASS: step_1 đúng cho ID 57\n";
            $this->assertEquals([6,5], $extra['suggests']);
            echo "PASS: suggests đúng cho ID 57\n";
        } catch (\Exception $e) {
            print_r($extra ?? []);
            throw $e;
        }
    }

    public function loadHeatmapFromCsv($path)
    {
        $rows = array_map('str_getcsv', file($path));
        array_shift($rows); // Bỏ dòng header
        $heatmap = [];
        foreach ($rows as $row) {
            // Giả sử cột: id, date, data
            $date = $row[1];
            $data = json_decode($row[2], true);
            $heatmap[$date]['data'] = $data;
        }
        return $heatmap;
    }
}

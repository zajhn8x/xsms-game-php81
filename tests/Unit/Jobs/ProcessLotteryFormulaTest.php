
<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\ProcessLotteryFormula;
use App\Models\LotteryResult;
use App\Models\LotteryCauLo;
use App\Services\LotteryFormulaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ProcessLotteryFormulaTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_lottery_formula()
    {
        // Create test data
        $cauLo = LotteryCauLo::factory()->create([
            'is_processed' => false
        ]);

        $result = LotteryResult::factory()->create([
            'draw_date' => Carbon::now()->format('Y-m-d')
        ]);

        // Run job
        $job = new ProcessLotteryFormula('test_batch');
        $job->handle(app(LotteryFormulaService::class));

        // Assert
        $this->assertTrue($cauLo->fresh()->is_processed);
        $this->assertNotNull($cauLo->fresh()->last_processed_date);
    }
}

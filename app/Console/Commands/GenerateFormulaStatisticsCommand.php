<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CreateFormulaStatisticsJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateFormulaStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formula:generate-statistics {formulaId} {startDate} {days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate formula statistics from FormulaHit within a specific date range, processed in quarterly batches';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $formulaId = (int) $this->argument('formulaId');
        $startDate = Carbon::parse($this->argument('startDate'));
        $days = (int) $this->argument('days');
        $endDate = $startDate->copy()->addDays($days - 1);

        while ($startDate <= $endDate) {
            // Xác định năm và quý của startDate
            $year = $startDate->year;
            $quarter = ceil($startDate->month / 3);

            // Xác định ngày bắt đầu và ngày kết thúc của quý hiện tại
            $quarterStart = Carbon::create($year, ($quarter - 1) * 3 + 1, 1)->startOfMonth();
            $quarterEnd = Carbon::create($year, $quarter * 3, 1)->endOfMonth();

            // Giới hạn phạm vi nếu vượt quá endDate
            if ($quarterStart < $startDate) {
                $quarterStart = $startDate->copy();
            }
            if ($quarterEnd > $endDate) {
                $quarterEnd = $endDate->copy();
            }
            Log::info("CreateFormulaStatisticsJob start id " . $formulaId . " - command: GenerateFormulaStatisticsCommand");
            // Dispatch job xử lý batch theo quý
            CreateFormulaStatisticsJob::dispatch($formulaId, $quarterStart->toDateString(), $quarterEnd->toDateString());
            $this->info("Dispatched job for Formula ID: $formulaId, from {$quarterStart->toDateString()} to {$quarterEnd->toDateString()}");

            // Chuyển startDate sang ngày đầu tiên của quý tiếp theo
            $startDate = $quarterEnd->copy()->addDay();
        }

        return 0;
    }
}

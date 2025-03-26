<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CreateFormulaStatisticsJob;
use Carbon\Carbon;

class GenerateFormulaStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formula:generate-statistics {formulaId} {startDate} {endDate}';

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
        $endDate = Carbon::parse($this->argument('endDate'));

        while ($startDate <= $endDate) {
            // Xác định quý hiện tại của startDate
            $year = $startDate->year;
            $quarter = ceil($startDate->month / 3);

            // Xác định ngày kết thúc của quý này
            $quarterEnd = Carbon::create($year, $quarter * 3, 1)->endOfMonth();

            if ($quarterEnd > $endDate) {
                $quarterEnd = $endDate;
            }

            // Dispatch job xử lý batch theo quý
            CreateFormulaStatisticsJob::dispatch($formulaId, $startDate->toDateString(), $quarterEnd->toDateString());
            $this->info("Dispatched job for Formula ID: $formulaId, from {$startDate->toDateString()} to {$quarterEnd->toDateString()}");

            // Chuyển sang batch tiếp theo
            $startDate = $quarterEnd->addDay();
        }

        return 0;
    }
}

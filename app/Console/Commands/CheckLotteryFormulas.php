<?php

namespace App\Console\Commands;

use App\Models\LotteryCauLo;
use App\Models\LotteryCauLoMeta;
use App\Models\LotteryResult;
use App\Models\LotteryCauLoHit;
use App\Services\LotteryIndexResultsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckLotteryFormulas extends Command
{
    protected $signature = 'lottery:check-formulas 
                            {--date= : Date to check formulas against (YYYY-MM-DD)}
                            {--days=1 : Number of days to check backwards from the date}
                            {--formula-id= : Specific formula ID to check}';

    protected $description = 'Check lottery formulas against results';

    /**
     * @var LotteryIndexResultsService
     */
    protected $lotteryIndexService;

    /**
     * Create a new command instance.
     *
     * @param LotteryIndexResultsService $lotteryIndexService
     * @return void
     */
    public function __construct(LotteryIndexResultsService $lotteryIndexService)
    {
        parent::__construct();
        $this->lotteryIndexService = $lotteryIndexService;
    }

    public function handle()
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $days = max(1, (int)$this->option('days'));
        $formulaId = $this->option('formula-id');

        $this->info("Checking formulas for {$days} days starting from {$date->format('Y-m-d')}");

        // Get formulas to check
        $query = LotteryCauLoMeta::query();
        if ($formulaId) {
            $query->where('id', $formulaId);
        }
        $formulas = $query->get();

        if ($formulas->isEmpty()) {
            $this->error('No formulas found to check.');
            return 1;
        }

        $this->info("Found {$formulas->count()} formulas to check.");

        // Get results for the specified date range
        $startDate = $date->copy()->subDays($days - 1);
        $endDate = $date;

        $results = LotteryResult::whereBetween('draw_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('draw_date')
            ->get();

        if ($results->isEmpty()) {
            $this->error('No lottery results found for the specified date range.');
            return 1;
        }

        $this->info("Found {$results->count()} days of results to check against.");

        // Process each formula
        $this->info(""); // Add empty line for better readability
        $this->info("--- Detailed Formula Results ---");

        foreach ($formulas as $formulaMeta) {
            $this->checkFormula($formulaMeta, $results);
        }

        $this->info("");
        $this->info('Formula checking completed.');

        return 0;
    }

    private function checkFormula($formulaMeta, $results)
    {
        $formulaStructure = json_decode($formulaMeta->formula_structure, true);
        $combinationType = $formulaMeta->combination_type;

        // Find or create the formula record
        $formula = LotteryCauLo::firstOrCreate(
            ['formula_meta_id' => $formulaMeta->id],
            [
                'combination_type' => $combinationType,
                'is_verified' => false,
                'hit_count' => 0,
                'miss_count' => 0,
                'is_active' => true
            ]
        );

        // Print formula details
        $this->info("");
        $this->info("Formula ID: {$formulaMeta->id} - Type: {$combinationType}");
        $this->line("Formula structure: " . json_encode($formulaStructure['positions'] ?? []));

        // Reset counters before checking
        $formula->hit_count = 0;
        $formula->miss_count = 0;

        $results->slice(0, $results->count() - 1)->each(function ($resultToday, $index) use ($results, $formulaStructure, $combinationType, &$formula) {
            $resultNextDay = $results->get($index + 1); // Lấy kết quả ngày kế tiếp

            if (!$resultNextDay) {
                return;
            }

            $hitNumbers = $this->extractNumbers($resultToday->draw_date, $formulaStructure, $combinationType);
            $drawDate = $resultToday->draw_date;
            $loArray = $resultNextDay->lo_array ?? [];
            $loToDayArray = $resultToday->lo_array ?? [];

            $hitNumbersStr = implode(', ', $hitNumbers);
            $loArrayStr = implode(', ', $loToDayArray);

            $hit = false;
            $matchedNumbers = collect($hitNumbers)->intersect($loArray)->all();

            if (!empty($matchedNumbers)) {
                $hit = true;
                $formula->hit_count++;

                // Save hit to database
                foreach ($matchedNumbers as $hitNumber) {
                    LotteryCauLoHit::create([
                        'cau_lo_id' => $formula->id,
                        'ngay' => $drawDate,
                        'so_trung' => $hitNumber
                    ]);
                }

                $this->line("<fg=green>✓ {$drawDate}: Numbers [{$hitNumbersStr}] - Hit on [" . implode(', ', $matchedNumbers) . "]</>");
                $this->line("  Lô array: [{$loArrayStr}]");
            } else {
                $formula->miss_count++;
                $this->line("<fg=red>✗ {$drawDate}: Numbers [{$hitNumbersStr}] - Miss</>");
                $this->line("  Lô array: [{$loArrayStr}]");
            }
        });

        // Print summary for this formula
        $totalDays = $formula->hit_count + $formula->miss_count;
        $hitRate = $totalDays > 0 ? round(($formula->hit_count / $totalDays) * 100, 2) : 0;
        $this->info("Hit rate: {$formula->hit_count}/{$totalDays} ({$hitRate}%)");

        // Update the formula verification status
        $formula->is_verified = true;
        $formula->last_date_verified = now();
        $formula->save();
    }

    private function extractNumbers($drawDate, $formulaStructure, $combinationType)
    {
        $positions = $formulaStructure['positions'] ?? [];
        $numbers = [];

        // Process based on combination type
        if ($combinationType === 'pair') {
            $this->extractPairNumbers($drawDate, $positions, $numbers);
        } elseif ($combinationType === 'single') {
            $this->extractSingleNumbers($drawDate, $positions, $numbers);
        } elseif ($combinationType === 'multi') {
            $this->extractMultiNumbers($drawDate, $positions, $numbers);
        } elseif ($combinationType === 'dynamic') {
            // Dynamic type would need a more complex implementation
            $this->extractDynamicNumbers($drawDate, $formulaStructure, $numbers);
        }

        return $numbers;
    }

    private function extractPairNumbers($drawDate, $positions, &$numbers)
    {
        if (count($positions) !== 2) {
            return;
        }

        $digits = [];

        foreach ($positions as $position) {
            $digit = $this->lotteryIndexService->getPositionValue($drawDate, $position);
            if ($digit !== null) {
                $digits[] = $digit;
            }
        }

        if (count($digits) === 2) {
            // Generate two-digit numbers from the pair
            $numbers[] = $digits[0] * 10 + $digits[1]; // XY
            $numbers[] = $digits[1] * 10 + $digits[0]; // YX
        }
    }

    private function extractSingleNumbers($drawDate, $positions, &$numbers)
    {
        if (count($positions) !== 1) {
            return;
        }

        $digit = $this->lotteryIndexService->getPositionValue($drawDate, $positions[0]);
        if ($digit !== null) {
            $numbers[] = $digit;
        }
    }

    private function extractMultiNumbers($drawDate, $positions, &$numbers)
    {
        if (count($positions) < 3) {
            return;
        }

        $digits = [];

        foreach ($positions as $position) {
            $digit = $this->lotteryIndexService->getPositionValue($drawDate, $position);
            if ($digit !== null) {
                $digits[] = $digit;
            }
        }

        // Generate all possible combinations of 2 digits
        $count = count($digits);
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                if ($i !== $j) {
                    $numbers[] = $digits[$i] * 10 + $digits[$j];
                }
            }
        }
    }

    private function extractDynamicNumbers($drawDate, $formulaStructure, &$numbers)
    {
        // This would need a more complex implementation based on your specific requirements
        // Placeholder for now
        return;
    }
}

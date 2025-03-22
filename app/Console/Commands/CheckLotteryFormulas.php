<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LotteryResult;
use App\Models\LotteryCauLo;
use App\Models\LotteryCauLoHit;
use App\Services\LotteryService;
use Carbon\Carbon;
use App\Models\LotteryCauLoMeta;


class CheckLotteryFormulas extends Command
{
    protected $signature = 'lottery:check-formulas';
    protected $description = 'Check lottery formulas against results';

    protected $lotteryService;

    public function __construct(LotteryService $lotteryService)
    {
        parent::__construct();
        $this->lotteryService = $lotteryService;
    }

    public function handle()
    {
        $this->info('Checking lottery formulas...');

        $today = Carbon::now()->format('Y-m-d');
        $results = $this->lotteryService->getResultsByDateRange(
            Carbon::now()->subDays(30),
            $today
        );

        foreach ($results as $result) {
            $formulas = LotteryCauLo::where('is_active', true)->get();

            foreach ($formulas as $formula) {
                $formulaMeta = LotteryCauLoMeta::find($formula->formula_meta_id);
                if(!$formulaMeta){
                    continue;
                }
                $hit = $this->checkFormulaHit($formulaMeta, $result);

                if ($hit) {
                    LotteryCauLoHit::create([
                        'cau_lo_id' => $formula->id,
                        'ngay' => $result->draw_date,
                        'so_trung' => $hit,
                        
                    ]);
                }
            }
        }

        $this->info('Formula check completed');
    }

    protected function checkFormulaHit($formulaMeta, $result)
    {
        $formulaStructure = json_decode($formulaMeta->formula_structure, true);
        $combinationType = $formulaMeta->combination_type;
        $hitNumbers = $this->extractNumbers($result->draw_date, $formulaStructure, $combinationType);
        $loArray = $result->lo_array ?? [];
        $matchedNumbers = collect($hitNumbers)->intersect($loArray)->all();
        if (!empty($matchedNumbers)) {
            return $matchedNumbers[0]; // Return the first matched number
        }
        return null;
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
            $digit = $this->lotteryService->getPositionValue($drawDate, $position);
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

        $digit = $this->lotteryService->getPositionValue($drawDate, $positions[0]);
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
            $digit = $this->lotteryService->getPositionValue($drawDate, $position);
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
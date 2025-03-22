<?php

namespace App\Console\Commands;

use App\Models\LotteryFormulaMeta;
use Illuminate\Console\Command;

class GeneratePairFormulas extends Command
{
    protected $signature = 'lottery:generate-pair-formulas {count=1000}';
    protected $description = 'Generate pair formulas for lottery predictions';

    public function handle()
    {
        $count = $this->argument('count');
        $this->info("Generating {$count} pair formulas...");

        $positions = config('xsmb.positions');
        $allPositions = [];

        // Flatten the positions array for easier access
        foreach ($positions as $prizeGroup => $positions) {
            foreach ($positions as $position) {
                $allPositions[] = $position;
            }
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $formulasGenerated = 0;
        $existingFormulas = LotteryFormulaMeta::where('combination_type', 'pair')
            ->pluck('formula_structure')
            ->toArray();

        // Convert existing formulas to a comparable format
        $existingFormulaHashes = [];
        foreach ($existingFormulas as $formula) {
            $formulaData = json_decode($formula, true);
            if (isset($formulaData['positions']) && count($formulaData['positions']) === 2) {
                sort($formulaData['positions']);
                $existingFormulaHashes[] = md5(json_encode($formulaData['positions']));
            }
        }

        $totalPositions = count($allPositions);
        $attempts = 0;
        $maxAttempts = $count * 10; // Limit the number of attempts to avoid infinite loops

        while ($formulasGenerated < $count && $attempts < $maxAttempts) {
            $attempts++;

            // Randomly select two different positions
            $position1Index = rand(0, $totalPositions - 1);
            $position2Index = rand(0, $totalPositions - 1);

            // Ensure we don't select the same position twice
            while ($position1Index === $position2Index) {
                $position2Index = rand(0, $totalPositions - 1);
            }

            $position1 = $allPositions[$position1Index];
            $position2 = $allPositions[$position2Index];

            // Sort positions to ensure consistency when checking for duplicates
            $selectedPositions = [$position1, $position2];
            sort($selectedPositions);

            // Check if this combination already exists
            $formulaHash = md5(json_encode($selectedPositions));
            if (in_array($formulaHash, $existingFormulaHashes)) {
                continue; // Skip this combination and try again
            }

            // Create formula name based on positions
            $formulaName = $this->generateFormulaName($selectedPositions);

            // Create formula structure
            $formulaStructure = [
                'positions' => $selectedPositions,
                'description' => "Ghép cầu lô từ vị trí {$selectedPositions[0]} và {$selectedPositions[1]}"
            ];

            // Save to database
            LotteryFormulaMeta::create([
                'formula_name' => $formulaName,
                'formula_note' => "Công thức ghép cầu lô tự động từ vị trí {$selectedPositions[0]} và {$selectedPositions[1]}",
                'formula_structure' => json_encode($formulaStructure),
                'combination_type' => 'pair'
            ]);

            $existingFormulaHashes[] = $formulaHash;
            $formulasGenerated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($formulasGenerated < $count) {
            $this->warn("Only generated {$formulasGenerated} unique formulas out of {$count} requested.");
            $this->info("You may have reached the maximum possible combinations or have too many existing formulas.");
        } else {
            $this->info("Successfully generated {$count} pair formulas.");
        }

        return 0;
    }

    private function generateFormulaName(array $positions)
    {
        // Extract prize groups from positions
        $groups = [];
        foreach ($positions as $position) {
            preg_match('/^(G[^-]+)/', $position, $matches);
            if (isset($matches[1])) {
                $groups[] = $matches[1];
            }
        }

        $uniqueGroups = array_unique($groups);
        sort($uniqueGroups);

        $groupNames = [
            'GDB' => 'ĐB',
            'G1' => 'G1',
            'G2' => 'G2',
            'G3' => 'G3',
            'G4' => 'G4',
            'G5' => 'G5',
            'G6' => 'G6',
            'G7' => 'G7',
        ];

        $groupLabels = [];
        foreach ($uniqueGroups as $group) {
            $groupLabels[] = $groupNames[$group] ?? $group;
        }

        return 'Cầu Lô Song Thủ ' . implode('-', $groupLabels) . ' #' . substr(md5(implode('', $positions)), 0, 5);
    }
}

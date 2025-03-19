
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contracts\LotteryResultServiceInterface;
use Carbon\Carbon;

class ImportLotteryData extends Command
{
    protected $signature = 'lottery:import {file : Path to the import file}';
    protected $description = 'Import lottery results data from CSV/JSON file';

    private $lotteryResultService;

    public function __construct(LotteryResultServiceInterface $lotteryResultService)
    {
        parent::__construct();
        $this->lotteryResultService = $lotteryResultService;
    }

    public function handle()
    {
        $file = $this->argument('file');
        
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        
        try {
            $data = match($extension) {
                'json' => $this->parseJson($file),
                'csv' => $this->parseCsv($file),
                default => throw new \Exception("Unsupported file type: {$extension}")
            };

            foreach ($data as $row) {
                $this->lotteryResultService->createResult($row);
                $this->info("Imported result for date: {$row['draw_date']}");
            }

            $this->info('Import completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
    }

    private function parseJson($file)
    {
        $content = file_get_contents($file);
        return json_decode($content, true);
    }

    private function parseCsv($file)
    {
        $data = [];
        $handle = fopen($file, 'r');
        
        // Skip header row
        $headers = fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            $prizes = [
                'special' => $row[1],
                'prize1' => $row[2],
                'prize2' => [$row[3], $row[4]],
                'prize3' => [$row[5], $row[6], $row[7], $row[8], $row[9], $row[10]],
                'prize4' => [$row[11], $row[12], $row[13], $row[14]],
                'prize5' => [$row[15], $row[16], $row[17], $row[18], $row[19], $row[20]],
                'prize6' => [$row[21], $row[22], $row[23]],
                'prize7' => [$row[24], $row[25], $row[26], $row[27]]
            ];

            // Generate lo_array from all prize numbers
            $lo_array = [];
            foreach ($prizes as $prize_numbers) {
                if (is_array($prize_numbers)) {
                    foreach ($prize_numbers as $number) {
                        if (strlen($number) >= 2) {
                            $lo_array[] = substr($number, -2);
                        }
                    }
                } else {
                    if (strlen($prize_numbers) >= 2) {
                        $lo_array[] = substr($prize_numbers, -2);
                    }
                }
            }

            $data[] = [
                'draw_date' => $row[0],
                'prizes' => $prizes,
                'lo_array' => array_unique($lo_array)
            ];
        }
        
        fclose($handle);
        return $data;
    }
}

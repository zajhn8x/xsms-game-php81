
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
            $data[] = [
                'draw_date' => $row[0],
                'prizes' => json_decode($row[1], true),
                'lo_array' => json_decode($row[2], true)
            ];
        }
        
        fclose($handle);
        return $data;
    }
}

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
        $handle = fopen($file, "r");
        $data = [];
        $handle = fopen($file, 'r');

        // Skip header row
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;

            try {
                $prizes = [
                    'special' => trim($row[1]),    // Giải đặc biệt (1 số)
                    'prize1'  => trim($row[2]),    // Giải nhất (1 số)
                    'prize2'  => array_map('trim', array_slice($row, 3, 2)),  // Giải nhì (2 số)
                    'prize3'  => array_map('trim', array_slice($row, 5, 6)),  // Giải ba (6 số)
                    'prize4'  => array_map('trim', array_slice($row, 11, 4)), // Giải tư (4 số)
                    'prize5'  => array_map('trim', array_slice($row, 15, 6)), // Giải năm (6 số)
                    'prize6'  => array_map('trim', array_slice($row, 21, 3)), // Giải sáu (3 số)
                    'prize7'  => array_map('trim', array_slice($row, 24, 4))  // Giải bảy (4 số)
                ];


                $lo_array = [];
                foreach ($prizes as $prize) {
                    $numbers = is_array($prize) ? $prize : [$prize];

                    foreach ($numbers as $number) {
                        if (!empty($number) && is_numeric($number)) {
                            $lo_array[] = sprintf('%02d', substr($number, -2));
                        }
                    }
                }

                // Đảm bảo mỗi ngày có đủ 27 giải (nếu thiếu thì log lỗi hoặc xử lý)
                if (count($lo_array) !== 27) {
                    error_log("Cảnh báo: Số lượng số lô không đúng 27! Hiện tại: " . count($lo_array));
                }

                $data[] = [
                    'draw_date' => $row[0],
                    'prizes' => $prizes,
                    'lo_array' => array_unique($lo_array)
                ];
            } catch (\Exception $e) {
                //Handle exceptions during row processing.  Log the error for debugging.
                $this->error("Error processing row: " . $e->getMessage());
                continue; //Skip this row and move on to the next.
            }
        }

        fclose($handle);
        return $data;
    }
}

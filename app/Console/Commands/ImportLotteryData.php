<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Contracts\LotteryResultServiceInterface;
use App\Models\LotteryResultIndex;
use Carbon\Carbon;

class ImportLotteryData extends Command
{
    protected $signature   = 'lottery:import {file : Path to the import file}';
    protected $description = 'Import lottery results data from CSV/JSON file';

    private $lotteryResultService;

    public function __construct(LotteryResultServiceInterface $lotteryResultService)
    {
        parent::__construct();
        $this->lotteryResultService = $lotteryResultService;
        $this->positions = config('xsmb.positions', []);
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
            $data = match ($extension) {
                'json' => $this->parseJson($file),
                'csv' => $this->parseCsv($file),
                default => throw new Exception("Unsupported file type: {$extension}")
            };
//            dd($this->positions);
            foreach ($data as $row) {
                $this->lotteryResultService->createResult($row);
                $positions = [];

                foreach ($this->positions as $prizeKey => $posList) {
                    if (!isset($row['prizes'][$prizeKey])) {
                        error_log("⚠️ Thiếu dữ liệu giải $prizeKey cho ngày {$row['draw_date']}");
                        continue;
                    }

                    $prizeNumbers = $row['prizes'][$prizeKey];

                    // Chuyển về mảng nếu chỉ có một giải
                    if (!is_array($prizeNumbers)) {
                        $prizeNumbers = [$prizeNumbers];
                    }

                    foreach ($posList as $posName) {
                        $parts = explode('-', $posName);
                        if (count($parts) < 3) continue;

                        $groupIndex = (int)$parts[1] - 1; // Chỉ mục nhóm giải (0-based)
                        $digitIndex = (int)$parts[2] - 1; // Chỉ mục chữ số (0-based)

                        if (!isset($prizeNumbers[$groupIndex])) {
                            error_log("⚠️ Không tìm thấy nhóm giải $prizeKey ($posName) cho ngày {$row['draw_date']}");
                            continue;
                        }

                        $prizeValue = (string)$prizeNumbers[$groupIndex];

                        if (!isset($prizeValue[$digitIndex])) continue;

                        $positions[] = [
                            'draw_date' => $row['draw_date'],
                            'position' => $posName,
                            'value' => (int)$prizeValue[$digitIndex],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Lưu dữ liệu vào bảng lottery_results_index
                if (!empty($positions)) {
                    LotteryResultIndex::upsert($positions, ['draw_date', 'position'], ['value', 'updated_at']);
                }

                $this->info("Imported result for date: {$row['draw_date']}");
            }

            $this->info('Import completed successfully!');
            return 0;
        } catch (Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
    }

    private function parseJson($file)
    {
        $content = file_get_contents($file);
        return json_decode($content, true);
    }

    // Hàm định dạng số theo đúng độ dài yêu cầu
    private function formatNumber($num, $length)
    {
        $num = trim($num);
        if (!is_numeric($num) || empty($num)) {
            return str_pad("", $length, "0", STR_PAD_LEFT); // Nếu lỗi trả về chuỗi 0 phù hợp
        }
        return str_pad($num, $length, '0', STR_PAD_LEFT);
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
                    'special' => $this->formatNumber($row[1], 5),    // Giải đặc biệt (5 số)
                    'prize1' => $this->formatNumber($row[2], 5),    // Giải nhất (5 số)
                    'prize2' => array_map(fn($num) => $this->formatNumber($num, 5), array_slice($row, 3, 2)),  // Giải nhì (2 số, mỗi số 5 chữ số)
                    'prize3' => array_map(fn($num) => $this->formatNumber($num, 5), array_slice($row, 5, 6)),  // Giải ba (6 số, mỗi số 5 chữ số)
                    'prize4' => array_map(fn($num) => $this->formatNumber($num, 4), array_slice($row, 11, 4)), // Giải tư (4 số, mỗi số 4 chữ số)
                    'prize5' => array_map(fn($num) => $this->formatNumber($num, 4), array_slice($row, 15, 6)), // Giải năm (6 số, mỗi số 4 chữ số)
                    'prize6' => array_map(fn($num) => $this->formatNumber($num, 3), array_slice($row, 21, 3)), // Giải sáu (3 số, mỗi số 3 chữ số)
                    'prize7' => array_map(fn($num) => $this->formatNumber($num, 2), array_slice($row, 24, 4))  // Giải bảy (4 số, mỗi số 2 chữ số)
                ];

                $lo_array = [];
                foreach ($row as $key => $number) {
                    if ($key != 0) {
                        $lo_array[] = $this->formatNumber(substr($number, -2), 2);
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
            } catch (Exception $e) {
                //Handle exceptions during row processing.  Log the error for debugging.
                $this->error("Error processing row: " . $e->getMessage());
                continue; //Skip this row and move on to the next.
            }
        }

        fclose($handle);
        return $data;
    }
}

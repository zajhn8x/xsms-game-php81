<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Contracts\LotteryResultServiceInterface;
use App\Models\LotteryResultIndex;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class ImportLotteryFromApi extends Command
{
    protected $signature   = 'lottery:import-api {days=7 : Số ngày cần lấy}';
    protected $description = 'Import lottery results data from API xoso188.net';

    private $lotteryResultService;
    private $positions;

    public function __construct(LotteryResultServiceInterface $lotteryResultService)
    {
        parent::__construct();
        $this->lotteryResultService = $lotteryResultService;
        $this->positions = config('xsmb.positions', []);
    }

    public function handle()
    {
        $days = $this->argument('days');

        try {
            // Gọi API lấy dữ liệu
            $response = Http::get("https://xoso188.net/api/front/open/lottery/history/list/{$days}/miba");

            if (!$response->successful()) {
                throw new Exception("Không thể kết nối tới API");
            }

            $apiData = $response->json();
            $processedData = [];

            // Xử lý dữ liệu từ API
            foreach ($apiData['issueList'] as $issue) {
                // Chuyển đổi định dạng ngày
                $drawDate = Carbon::createFromFormat('d/m/Y', $issue['turnNum'])->format('Y-m-d');

                // Parse detail string thành array
                $details = json_decode($issue['detail'], true);

                // Chuyển đổi dữ liệu theo cấu trúc cũ
                $prizes = [
                    'special' => $this->formatNumber($details[0], 5), // Giải đặc biệt
                    'prize1' => $this->formatNumber($details[1], 5), // Giải nhất
                    'prize2' => array_map(fn($num) => $this->formatNumber($num, 5), explode(',', $details[2])), // Giải nhì
                    'prize3' => array_map(fn($num) => $this->formatNumber($num, 5), explode(',', $details[3])), // Giải ba
                    'prize4' => array_map(fn($num) => $this->formatNumber($num, 4), explode(',', $details[4])), // Giải tư
                    'prize5' => array_map(fn($num) => $this->formatNumber($num, 4), explode(',', $details[5])), // Giải năm
                    'prize6' => array_map(fn($num) => $this->formatNumber($num, 3), explode(',', $details[6])), // Giải sáu
                    'prize7' => array_map(fn($num) => $this->formatNumber($num, 2), explode(',', $details[7]))  // Giải bảy
                ];

                // Tạo mảng lô 2 số
                $lo_array = [];
                foreach ($prizes as $prizeNumbers) {
                    if (is_array($prizeNumbers)) {
                        foreach ($prizeNumbers as $number) {
                            $lo_array[] = substr($number, -2);
                        }
                    } else {
                        $lo_array[] = substr($prizeNumbers, -2);
                    }
                }

                $processedData[] = [
                    'draw_date' => $drawDate,
                    'prizes' => $prizes,
                    'lo_array' => array_unique($lo_array)
                ];
            }

            // Lưu dữ liệu và xử lý index như ImportLotteryData
            foreach ($processedData as $row) {
                $this->lotteryResultService->createResult($row);
                $positions = [];

                foreach ($this->positions as $prizeKey => $posList) {
                    if (!isset($row['prizes'][$prizeKey])) {
                        error_log("⚠️ Thiếu dữ liệu giải $prizeKey cho ngày {$row['draw_date']}");
                        continue;
                    }

                    $prizeNumbers = $row['prizes'][$prizeKey];
                    if (!is_array($prizeNumbers)) {
                        $prizeNumbers = [$prizeNumbers];
                    }

                    foreach ($posList as $posName) {
                        $parts = explode('-', $posName);
                        if (count($parts) < 3) continue;

                        $groupIndex = (int)$parts[1] - 1;
                        $digitIndex = (int)$parts[2] - 1;

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

    // Hàm định dạng số theo đúng độ dài yêu cầu
    private function formatNumber($num, $length)
    {
        $num = trim($num);
        if (!is_numeric($num) || empty($num)) {
            return str_pad("", $length, "0", STR_PAD_LEFT);
        }
        return str_pad($num, $length, '0', STR_PAD_LEFT);
    }
}

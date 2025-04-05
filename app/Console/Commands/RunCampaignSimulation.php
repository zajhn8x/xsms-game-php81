
<?php

namespace App\Console\Commands;

use App\Models\FormulaHit;
use App\Models\LotteryResult;
use App\Services\FormulaHitService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunCampaignSimulation extends Command
{
    protected $signature = 'lo:run-campaign {cau_lo_id} {start_date} {so_ngay}';
    protected $description = 'Chạy mô phỏng chiến dịch đánh lô với công thức cụ thể';

    protected $formulaHitService;

    public function __construct(FormulaHitService $formulaHitService) 
    {
        parent::__construct();
        $this->formulaHitService = $formulaHitService;
    }

    public function handle()
    {
        // Lấy tham số đầu vào
        $cauLoId = $this->argument('cau_lo_id');
        $startDate = Carbon::parse($this->argument('start_date')); 
        $days = (int)$this->argument('so_ngay');
        
        // Khởi tạo biến theo dõi
        $totalBalance = 0; // Tổng tiền lãi/lỗ
        $isFirstBet = true; // Đánh dấu lần đánh đầu tiên

        $this->info("Bắt đầu mô phỏng chiến dịch...");
        $this->info("ID Công thức: {$cauLoId}");
        $this->info("Ngày bắt đầu: {$startDate->format('Y-m-d')}");
        $this->info("Số ngày chạy: {$days}");

        // Chạy mô phỏng theo số ngày
        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            
            // Lấy thông tin streak từ FormulaHitService
            $streakFormulas = $this->formulaHitService->getStreakFormulas($currentDate, 1, 1);
            $hasStreak = $streakFormulas->contains('cau_lo_id', $cauLoId);
            
            if ($hasStreak) {
                // Lấy số dự đoán từ công thức
                $hit = FormulaHit::where('cau_lo_id', $cauLoId)
                    ->where('ngay', $currentDate->format('Y-m-d'))
                    ->first();

                if (!$hit) {
                    $this->info("Không tìm thấy kết quả cho ngày: {$currentDate->format('Y-m-d')}");
                    continue;
                }

                // Tính điểm đánh dựa vào lần đánh
                $points = $isFirstBet ? 10 : 5; // Lần đầu 10 điểm, sau 5 điểm
                $betAmount = $points * 23000; // 1 điểm = 23.000đ
                
                // Kiểm tra kết quả thắng thua
                $result = LotteryResult::where('draw_date', $currentDate->copy()->addDay()->format('Y-m-d'))->first();
                $isWin = $result && in_array($hit->so, $result->lo_array);
                $winAmount = $isWin ? $points * 80000 : 0; // Trúng ăn 80.000đ/điểm
                
                // Tính lãi/lỗ
                $profitLoss = $winAmount - $betAmount;
                $totalBalance += $profitLoss;

                // Ghi log kết quả
                $this->logBet([
                    'date' => $currentDate->format('Y-m-d'),
                    'points' => $points,
                    'number' => $hit->so,
                    'bet_amount' => $betAmount,
                    'win_amount' => $winAmount,
                    'profit_loss' => $profitLoss,
                    'total_balance' => $totalBalance
                ]);

                $isFirstBet = false;
            }
        }

        $this->info("\nKết quả cuối cùng:");
        $this->info("Tổng tiền lãi/lỗ: " . number_format($totalBalance) . " VND");
    }

    /**
     * Ghi log thông tin đánh
     */
    private function logBet($data)
    {
        $this->info("\nThông tin đánh ngày {$data['date']}:");
        $this->info("Số điểm: {$data['points']}");
        $this->info("Số đánh: {$data['number']}");
        $this->info("Tiền đánh: " . number_format($data['bet_amount']));
        $this->info("Tiền thắng: " . number_format($data['win_amount']));
        $this->info("Lãi/Lỗ: " . number_format($data['profit_loss']));
        $this->info("Tổng lũy kế: " . number_format($data['total_balance']));
        
        Log::info('Kết quả mô phỏng chiến dịch', $data);
    }
}

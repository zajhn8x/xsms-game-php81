
<?php

namespace App\Console\Commands;

use App\Models\FormulaHit;
use App\Models\LotteryResult;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunCampaignSimulation extends Command
{
    protected $signature = 'lo:run-campaign {cau_lo_id} {start_date} {so_ngay}';
    protected $description = 'Run lottery campaign simulation with specific formula';

    public function handle()
    {
        $cauLoId = $this->argument('cau_lo_id');
        $startDate = Carbon::parse($this->argument('start_date'));
        $days = (int)$this->argument('so_ngay');
        
        $totalBalance = 0;
        $currentPoints = 0;
        $isFirstBet = true;

        $this->info("Starting campaign simulation...");
        $this->info("Formula ID: {$cauLoId}");
        $this->info("Start date: {$startDate->format('Y-m-d')}");
        $this->info("Days to run: {$days}");

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            
            // Get streak info from past 6 days
            $streak = $this->getStreak($cauLoId, $currentDate);
            
            if ($streak == 1) {
                // Get lottery number from the formula hit
                $hit = FormulaHit::where('cau_lo_id', $cauLoId)
                    ->where('ngay', $currentDate->format('Y-m-d'))
                    ->first();

                if (!$hit) {
                    $this->info("No hit found for date: {$currentDate->format('Y-m-d')}");
                    continue;
                }

                // Set points based on if it's first bet
                $points = $isFirstBet ? 10 : 5;
                $betAmount = $points * 23000;
                
                // Check if we win
                $result = LotteryResult::where('draw_date', $currentDate->copy()->addDay()->format('Y-m-d'))->first();
                $isWin = $result && in_array($hit->so, $result->lo_array);
                $winAmount = $isWin ? $points * 80000 : 0;
                
                // Calculate profit/loss
                $profitLoss = $winAmount - $betAmount;
                $totalBalance += $profitLoss;

                // Log results
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

        $this->info("\nFinal Results:");
        $this->info("Total Balance: " . number_format($totalBalance) . " VND");
    }

    private function getStreak($cauLoId, $date)
    {
        $pastDays = FormulaHit::where('cau_lo_id', $cauLoId)
            ->where('ngay', '<=', $date)
            ->orderBy('ngay', 'desc')
            ->take(6)
            ->get();

        $streak = 0;
        foreach ($pastDays as $hit) {
            if ($hit->trung) {
                $streak++;
            } else {
                break;
            }
        }
        
        return $streak;
    }

    private function logBet($data)
    {
        $this->info("\nBet Details for {$data['date']}:");
        $this->info("Points: {$data['points']}");
        $this->info("Number: {$data['number']}");
        $this->info("Bet Amount: " . number_format($data['bet_amount']));
        $this->info("Win Amount: " . number_format($data['win_amount']));
        $this->info("Profit/Loss: " . number_format($data['profit_loss']));
        $this->info("Total Balance: " . number_format($data['total_balance']));
        
        Log::info('Campaign Simulation Bet', $data);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\HistoricalCampaign;
use App\Models\User;
use App\Services\HistoricalTestingService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RunHistoricalCampaignTest extends Command
{
    protected $signature = 'campaign:test-historical
                           {--start-date=2005-10-01 : Ngày bắt đầu test (Y-m-d)}
                           {--end-date=2005-10-31 : Ngày kết thúc test (Y-m-d)}
                           {--balance=1000000 : Số dư ban đầu (VNĐ)}
                           {--strategy=manual : Chiến thuật (manual, auto_heatmap, auto_streak)}
                           {--bet-amount=10000 : Số tiền đặt mỗi lần (VNĐ)}
                           {--numbers= : Các số target (phân cách bằng dấu phẩy, để trống = random)}
                           {--max-bets=3 : Số lần đặt tối đa mỗi ngày}
                           {--user-email=test@example.com : Email user test}
                           {--compare : So sánh nhiều chiến thuật}';

    protected $description = 'Chạy test chiến dịch với dữ liệu lịch sử';

    public function handle()
    {
        $this->info('🚀 BẮT ĐẦU TEST CHIẾN DỊCH LỊCH SỬ');
        $this->info('=================================');

        // Tạo hoặc lấy user test
        $user = User::firstOrCreate([
            'email' => $this->option('user-email')
        ], [
            'name' => 'Historical Test User',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ]);

        $this->info("👤 User: {$user->name} ({$user->email})");

        $historicalTestingService = new HistoricalTestingService();

        if ($this->option('compare')) {
            $this->runComparisonTest($user, $historicalTestingService);
        } else {
            $this->runSingleTest($user, $historicalTestingService);
        }
    }

    private function runSingleTest(User $user, HistoricalTestingService $service)
    {
        $config = $this->buildCampaignConfig();

        $this->info("\n📝 Tạo campaign...");
        $this->table(['Thuộc tính', 'Giá trị'], [
            ['Ngày bắt đầu', $config['test_start_date']],
            ['Ngày kết thúc', $config['test_end_date']],
            ['Số dư ban đầu', number_format($config['initial_balance']) . ' VNĐ'],
            ['Chiến thuật', $config['betting_strategy']],
            ['Số tiền đặt', number_format($config['strategy_config']['bet_amount']) . ' VNĐ'],
            ['Số target', implode(', ', $config['strategy_config']['target_numbers'] ?: ['Random'])],
            ['Max bets/ngày', $config['strategy_config']['max_daily_bets']]
        ]);

        $campaign = $service->createTestCampaign($user->id, $config);
        $this->info("✅ Đã tạo campaign ID: {$campaign->id}");

        $this->info("\n⚡ Bắt đầu chạy test...");
        $progressBar = $this->output->createProgressBar(31); // 31 days in October
        $progressBar->start();

        try {
            $service->runHistoricalTest($campaign->id);
            $progressBar->finish();

            $campaign->refresh();
            $this->displayResults($campaign);

        } catch (\Exception $e) {
            $progressBar->finish();
            $this->error("\n❌ Lỗi: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function runComparisonTest(User $user, HistoricalTestingService $service)
    {
        $this->info("\n🔬 CHẠY TEST SO SÁNH NHIỀU CHIẾN THUẬT");
        $this->info("=====================================");

        $strategies = [
            'conservative' => [
                'name' => 'Conservative',
                'bet_amount' => 5000,
                'target_numbers' => ['01', '11', '22', '33'],
                'max_daily_bets' => 2
            ],
            'aggressive' => [
                'name' => 'Aggressive',
                'bet_amount' => 20000,
                'target_numbers' => ['07', '17', '27', '37', '47', '57'],
                'max_daily_bets' => 5
            ],
            'balanced' => [
                'name' => 'Balanced',
                'bet_amount' => 10000,
                'target_numbers' => ['12', '34', '56'],
                'max_daily_bets' => 3
            ]
        ];

        $results = [];

        foreach ($strategies as $key => $strategy) {
            $this->info("\n🔄 Test chiến thuật: {$strategy['name']}");

            $config = [
                'name' => "Test {$strategy['name']} - " . $this->option('start-date'),
                'description' => "Test chiến thuật {$strategy['name']}",
                'test_start_date' => $this->option('start-date'),
                'test_end_date' => $this->option('end-date'),
                'initial_balance' => $this->option('balance'),
                'betting_strategy' => 'manual',
                'strategy_config' => [
                    'bet_amount' => $strategy['bet_amount'],
                    'target_numbers' => $strategy['target_numbers'],
                    'max_daily_bets' => $strategy['max_daily_bets']
                ]
            ];

            try {
                $campaign = $service->createTestCampaign($user->id, $config);
                $service->runHistoricalTest($campaign->id);
                $campaign->refresh();

                $results[$key] = [
                    'name' => $strategy['name'],
                    'campaign' => $campaign,
                    'roi' => $campaign->profit_percentage,
                    'profit' => $campaign->profit,
                    'win_rate' => $campaign->win_rate,
                    'total_bets' => $campaign->bets()->count()
                ];

                $this->info("✅ ROI: " . number_format($campaign->profit_percentage, 2) . "%");

            } catch (\Exception $e) {
                $this->error("❌ Lỗi: " . $e->getMessage());
                $results[$key] = null;
            }
        }

        $this->displayComparisonResults($results);
    }

    private function buildCampaignConfig(): array
    {
        $targetNumbers = $this->option('numbers')
            ? explode(',', $this->option('numbers'))
            : [];

        return [
            'name' => 'Historical Test - ' . $this->option('start-date'),
            'description' => 'Test chiến dịch với dữ liệu lịch sử',
            'test_start_date' => $this->option('start-date'),
            'test_end_date' => $this->option('end-date'),
            'initial_balance' => $this->option('balance'),
            'betting_strategy' => $this->option('strategy'),
            'strategy_config' => [
                'bet_amount' => $this->option('bet-amount'),
                'target_numbers' => $targetNumbers,
                'max_daily_bets' => $this->option('max-bets')
            ]
        ];
    }

    private function displayResults(HistoricalCampaign $campaign)
    {
        $this->info("\n\n🎯 KẾT QUẢ CUỐI CÙNG");
        $this->info("===================");

        $this->table(['Chỉ số', 'Giá trị'], [
            ['Trạng thái', $campaign->status],
            ['Số dư ban đầu', number_format($campaign->initial_balance) . ' VNĐ'],
            ['Số dư cuối cùng', number_format($campaign->final_balance) . ' VNĐ'],
            ['Lãi/Lỗ', number_format($campaign->profit) . ' VNĐ'],
            ['ROI', number_format($campaign->profit_percentage, 2) . '%'],
            ['Tổng lần đặt', $campaign->bets()->count()],
            ['Lần thắng', $campaign->bets()->where('is_win', true)->count()],
            ['Tỷ lệ thắng', round($campaign->win_rate, 2) . '%'],
            ['Thời gian test', $campaign->duration . ' ngày']
        ]);

        // Top 5 lần đặt thắng lớn nhất
        $bigWins = $campaign->bets()
            ->where('is_win', true)
            ->orderByDesc('win_amount')
            ->take(5)
            ->get();

        if ($bigWins->count() > 0) {
            $this->info("\n🏆 TOP 5 LẦN THẮNG LỚN NHẤT:");
            foreach ($bigWins as $bet) {
                $profit = $bet->win_amount - $bet->amount;
                $this->line("• {$bet->bet_date} | Số {$bet->lo_number} | +" . number_format($profit) . " VNĐ");
            }
        }

        $this->info("\n💡 Xem chi tiết tại: /historical-testing/{$campaign->id}");
    }

    private function displayComparisonResults(array $results)
    {
        $this->info("\n\n🏆 KẾT QUẢ SO SÁNH");
        $this->info("==================");

        // Lọc và sắp xếp kết quả
        $validResults = array_filter($results);
        uasort($validResults, fn($a, $b) => $b['roi'] <=> $a['roi']);

        $tableData = [];
        $rank = 1;

        foreach ($validResults as $result) {
            $tableData[] = [
                "#{$rank}",
                $result['name'],
                number_format($result['roi'], 2) . '%',
                number_format($result['profit']),
                round($result['win_rate'], 1) . '%',
                $result['total_bets']
            ];
            $rank++;
        }

        $this->table([
            'Hạng', 'Chiến thuật', 'ROI', 'Lãi/Lỗ (VNĐ)', 'Tỷ lệ thắng', 'Số lần đặt'
        ], $tableData);

        // Hiển thị campaign ID để xem chi tiết
        $this->info("\n💡 Xem chi tiết:");
        foreach ($validResults as $key => $result) {
            $this->line("• {$result['name']}: /historical-testing/{$result['campaign']->id}");
        }
    }
}

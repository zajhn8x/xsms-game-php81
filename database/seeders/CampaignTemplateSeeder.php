<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CampaignTemplate;

/**
 * Micro-task 2.1.2.4: Pre-built templates seeder (4h)
 * Seed system templates for campaign creation
 */
class CampaignTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $systemTemplates = [
            [
                'name' => 'Conservative Betting',
                'description' => 'Chiến lược đặt cược thận trọng với giới hạn thua thấp. Phù hợp cho người mới bắt đầu hoặc muốn bảo toàn vốn.',
                'category' => 'system',
                'user_id' => null,
                'is_public' => true,
                'template_data' => [
                    'campaign_type' => 'live',
                    'initial_balance' => 1000000,
                    'daily_bet_limit' => 50000,
                    'max_loss_per_day' => 100000,
                    'total_loss_limit' => 300000,
                    'auto_stop_loss' => true,
                    'auto_take_profit' => false,
                    'stop_loss_amount' => 300000,
                    'take_profit_amount' => null,
                    'betting_strategy' => 'manual',
                    'strategy_config' => [],
                    'days' => 30,
                    'target_profit' => 200000,
                    'notes' => 'Template thận trọng với giới hạn rủi ro chặt chẽ'
                ],
                'usage_count' => 0,
                'rating' => 0
            ],
            [
                'name' => 'Aggressive Growth',
                'description' => 'Chiến lược tăng trưởng tích cực với mục tiêu lợi nhuận cao. Phù hợp cho người có kinh nghiệm và chấp nhận rủi ro cao.',
                'category' => 'system',
                'user_id' => null,
                'is_public' => true,
                'template_data' => [
                    'campaign_type' => 'live',
                    'initial_balance' => 2000000,
                    'daily_bet_limit' => 200000,
                    'max_loss_per_day' => 300000,
                    'total_loss_limit' => 800000,
                    'auto_stop_loss' => true,
                    'auto_take_profit' => true,
                    'stop_loss_amount' => 800000,
                    'take_profit_amount' => 1000000,
                    'betting_strategy' => 'auto_heatmap',
                    'strategy_config' => [
                        'min_confidence' => 0.7,
                        'max_numbers_per_day' => 5,
                        'bet_multiplier' => 1.5
                    ],
                    'days' => 60,
                    'target_profit' => 1000000,
                    'notes' => 'Template tăng trưởng mạnh với chiến lược tự động'
                ],
                'usage_count' => 0,
                'rating' => 0
            ],
            [
                'name' => 'Balanced Strategy',
                'description' => 'Chiến lược cân bằng giữa bảo toàn vốn và tăng trưởng. Phù hợp cho đa số người chơi.',
                'category' => 'system',
                'user_id' => null,
                'is_public' => true,
                'template_data' => [
                    'campaign_type' => 'live',
                    'initial_balance' => 1500000,
                    'daily_bet_limit' => 100000,
                    'max_loss_per_day' => 150000,
                    'total_loss_limit' => 500000,
                    'auto_stop_loss' => true,
                    'auto_take_profit' => true,
                    'stop_loss_amount' => 500000,
                    'take_profit_amount' => 500000,
                    'betting_strategy' => 'auto_streak',
                    'strategy_config' => [
                        'min_streak' => 3,
                        'max_numbers' => 3,
                        'confidence_threshold' => 0.6
                    ],
                    'days' => 45,
                    'target_profit' => 500000,
                    'notes' => 'Template cân bằng phù hợp cho người chơi trung bình'
                ],
                'usage_count' => 0,
                'rating' => 0
            ],
            [
                'name' => 'Quick Test Run',
                'description' => 'Template thử nghiệm nhanh với số tiền ít để test chiến lược trong thời gian ngắn.',
                'category' => 'system',
                'user_id' => null,
                'is_public' => true,
                'template_data' => [
                    'campaign_type' => 'live',
                    'initial_balance' => 500000,
                    'daily_bet_limit' => 50000,
                    'max_loss_per_day' => 100000,
                    'total_loss_limit' => 200000,
                    'auto_stop_loss' => true,
                    'auto_take_profit' => true,
                    'stop_loss_amount' => 200000,
                    'take_profit_amount' => 200000,
                    'betting_strategy' => 'manual',
                    'strategy_config' => [],
                    'days' => 15,
                    'target_profit' => 100000,
                    'notes' => 'Template test ngắn hạn với vốn nhỏ'
                ],
                'usage_count' => 0,
                'rating' => 0
            ],
            [
                'name' => 'Historical Backtesting',
                'description' => 'Template cho việc test chiến lược với dữ liệu lịch sử để đánh giá hiệu quả.',
                'category' => 'system',
                'user_id' => null,
                'is_public' => true,
                'template_data' => [
                    'campaign_type' => 'historical',
                    'initial_balance' => 1000000,
                    'daily_bet_limit' => 100000,
                    'max_loss_per_day' => null,
                    'total_loss_limit' => null,
                    'auto_stop_loss' => false,
                    'auto_take_profit' => false,
                    'stop_loss_amount' => null,
                    'take_profit_amount' => null,
                    'betting_strategy' => 'auto_pattern',
                    'strategy_config' => [
                        'pattern_length' => 7,
                        'min_matches' => 3,
                        'confidence_level' => 0.8
                    ],
                    'days' => 90,
                    'target_profit' => null,
                    'notes' => 'Template cho backtesting với dữ liệu lịch sử'
                ],
                'usage_count' => 0,
                'rating' => 0
            ],
            [
                'name' => 'Long Term Investment',
                'description' => 'Chiến lược đầu tư dài hạn với mục tiêu tăng trưởng ổn định theo thời gian.',
                'category' => 'system',
                'user_id' => null,
                'is_public' => true,
                'template_data' => [
                    'campaign_type' => 'live',
                    'initial_balance' => 5000000,
                    'daily_bet_limit' => 150000,
                    'max_loss_per_day' => 200000,
                    'total_loss_limit' => 1500000,
                    'auto_stop_loss' => true,
                    'auto_take_profit' => false,
                    'stop_loss_amount' => 1500000,
                    'take_profit_amount' => null,
                    'betting_strategy' => 'auto_hybrid',
                    'strategy_config' => [
                        'strategies' => ['heatmap', 'streak', 'pattern'],
                        'weight_distribution' => [0.4, 0.35, 0.25],
                        'rebalance_frequency' => 'weekly'
                    ],
                    'days' => 180,
                    'target_profit' => 2000000,
                    'notes' => 'Template đầu tư dài hạn với chiến lược hybrid'
                ],
                'usage_count' => 0,
                'rating' => 0
            ]
        ];

        foreach ($systemTemplates as $template) {
            CampaignTemplate::create($template);
        }

        $this->command->info('Created ' . count($systemTemplates) . ' system campaign templates.');
    }
}

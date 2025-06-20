<?php

namespace App\Services;

/**
 * Micro-task 2.1.3.4: Risk assessment validation (3h)
 * Service for calculating and validating campaign risk levels
 */
class CampaignRiskAssessmentService
{
    /**
     * Risk level constants
     */
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_EXTREME = 'extreme';

    /**
     * Risk thresholds
     */
    const RISK_THRESHOLDS = [
        'loss_ratio' => [
            'medium' => 0.2,    // 20% of initial balance
            'high' => 0.4,      // 40% of initial balance
            'extreme' => 0.6    // 60% of initial balance
        ],
        'daily_risk_ratio' => [
            'medium' => 0.1,    // 10% daily risk
            'high' => 0.2,      // 20% daily risk
            'extreme' => 0.3    // 30% daily risk
        ],
        'profit_risk_ratio' => [
            'medium' => 3,      // 3x initial balance target
            'high' => 5,        // 5x initial balance target
            'extreme' => 10     // 10x initial balance target
        ]
    ];

    /**
     * Calculate comprehensive risk assessment for campaign
     */
    public function assessCampaignRisk(array $campaignData): array
    {
        $initialBalance = $campaignData['initial_balance'] ?? 0;
        $totalLossLimit = $campaignData['total_loss_limit'] ?? 0;
        $dailyLossLimit = $campaignData['max_loss_per_day'] ?? 0;
        $dailyBetLimit = $campaignData['daily_bet_limit'] ?? 0;
        $targetProfit = $campaignData['target_profit'] ?? 0;
        $bettingStrategy = $campaignData['betting_strategy'] ?? 'manual';
        $days = $campaignData['days'] ?? 30;

        // Calculate individual risk factors
        $lossRisk = $this->calculateLossRisk($initialBalance, $totalLossLimit);
        $dailyRisk = $this->calculateDailyRisk($initialBalance, $dailyLossLimit, $dailyBetLimit);
        $profitRisk = $this->calculateProfitRisk($initialBalance, $targetProfit);
        $strategyRisk = $this->calculateStrategyRisk($bettingStrategy, $campaignData['strategy_config'] ?? []);
        $timeRisk = $this->calculateTimeRisk($days, $initialBalance, $totalLossLimit);

        // Calculate overall risk level
        $overallRisk = $this->calculateOverallRisk([
            'loss' => $lossRisk,
            'daily' => $dailyRisk,
            'profit' => $profitRisk,
            'strategy' => $strategyRisk,
            'time' => $timeRisk
        ]);

        // Generate risk score (0-100)
        $riskScore = $this->calculateRiskScore($overallRisk, [
            'loss' => $lossRisk,
            'daily' => $dailyRisk,
            'profit' => $profitRisk,
            'strategy' => $strategyRisk,
            'time' => $timeRisk
        ]);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($overallRisk, [
            'loss' => $lossRisk,
            'daily' => $dailyRisk,
            'profit' => $profitRisk,
            'strategy' => $strategyRisk,
            'time' => $timeRisk
        ], $campaignData);

        return [
            'overall_risk' => $overallRisk,
            'risk_score' => $riskScore,
            'risk_factors' => [
                'loss_risk' => $lossRisk,
                'daily_risk' => $dailyRisk,
                'profit_risk' => $profitRisk,
                'strategy_risk' => $strategyRisk,
                'time_risk' => $timeRisk
            ],
            'recommendations' => $recommendations,
            'warnings' => $this->generateWarnings($overallRisk, $campaignData),
            'suggested_limits' => $this->suggestOptimalLimits($initialBalance, $overallRisk)
        ];
    }

    /**
     * Calculate loss risk based on total loss limit vs initial balance
     */
    protected function calculateLossRisk(float $initialBalance, float $totalLossLimit): array
    {
        if ($initialBalance <= 0) {
            return ['level' => self::RISK_EXTREME, 'ratio' => 1.0, 'description' => 'Số dư ban đầu không hợp lệ'];
        }

        if ($totalLossLimit <= 0) {
            return ['level' => self::RISK_LOW, 'ratio' => 0.0, 'description' => 'Không có giới hạn thua'];
        }

        $lossRatio = $totalLossLimit / $initialBalance;

        if ($lossRatio >= self::RISK_THRESHOLDS['loss_ratio']['extreme']) {
            $level = self::RISK_EXTREME;
            $description = 'Rủi ro thua lỗ cực kỳ cao';
        } elseif ($lossRatio >= self::RISK_THRESHOLDS['loss_ratio']['high']) {
            $level = self::RISK_HIGH;
            $description = 'Rủi ro thua lỗ cao';
        } elseif ($lossRatio >= self::RISK_THRESHOLDS['loss_ratio']['medium']) {
            $level = self::RISK_MEDIUM;
            $description = 'Rủi ro thua lỗ trung bình';
        } else {
            $level = self::RISK_LOW;
            $description = 'Rủi ro thua lỗ thấp';
        }

        return [
            'level' => $level,
            'ratio' => $lossRatio,
            'description' => $description,
            'percentage' => round($lossRatio * 100, 1) . '%'
        ];
    }

    /**
     * Calculate daily risk based on daily limits
     */
    protected function calculateDailyRisk(float $initialBalance, float $dailyLossLimit, float $dailyBetLimit): array
    {
        if ($initialBalance <= 0) {
            return ['level' => self::RISK_EXTREME, 'ratio' => 1.0, 'description' => 'Số dư ban đầu không hợp lệ'];
        }

        $dailyLossRatio = $dailyLossLimit > 0 ? $dailyLossLimit / $initialBalance : 0;
        $dailyBetRatio = $dailyBetLimit > 0 ? $dailyBetLimit / $initialBalance : 0;

        $maxDailyRatio = max($dailyLossRatio, $dailyBetRatio * 0.5); // Assume 50% loss rate on bets

        if ($maxDailyRatio >= self::RISK_THRESHOLDS['daily_risk_ratio']['extreme']) {
            $level = self::RISK_EXTREME;
            $description = 'Rủi ro hàng ngày cực kỳ cao';
        } elseif ($maxDailyRatio >= self::RISK_THRESHOLDS['daily_risk_ratio']['high']) {
            $level = self::RISK_HIGH;
            $description = 'Rủi ro hàng ngày cao';
        } elseif ($maxDailyRatio >= self::RISK_THRESHOLDS['daily_risk_ratio']['medium']) {
            $level = self::RISK_MEDIUM;
            $description = 'Rủi ro hàng ngày trung bình';
        } else {
            $level = self::RISK_LOW;
            $description = 'Rủi ro hàng ngày thấp';
        }

        return [
            'level' => $level,
            'ratio' => $maxDailyRatio,
            'description' => $description,
            'daily_loss_ratio' => $dailyLossRatio,
            'daily_bet_ratio' => $dailyBetRatio
        ];
    }

    /**
     * Calculate profit risk based on target profit vs initial balance
     */
    protected function calculateProfitRisk(float $initialBalance, float $targetProfit): array
    {
        if ($initialBalance <= 0) {
            return ['level' => self::RISK_EXTREME, 'ratio' => 0, 'description' => 'Số dư ban đầu không hợp lệ'];
        }

        if ($targetProfit <= 0) {
            return ['level' => self::RISK_LOW, 'ratio' => 0, 'description' => 'Không có mục tiêu lợi nhuận'];
        }

        $profitRatio = $targetProfit / $initialBalance;

        if ($profitRatio >= self::RISK_THRESHOLDS['profit_risk_ratio']['extreme']) {
            $level = self::RISK_EXTREME;
            $description = 'Mục tiêu lợi nhuận phi thực tế';
        } elseif ($profitRatio >= self::RISK_THRESHOLDS['profit_risk_ratio']['high']) {
            $level = self::RISK_HIGH;
            $description = 'Mục tiêu lợi nhuận rất cao';
        } elseif ($profitRatio >= self::RISK_THRESHOLDS['profit_risk_ratio']['medium']) {
            $level = self::RISK_MEDIUM;
            $description = 'Mục tiêu lợi nhuận cao';
        } else {
            $level = self::RISK_LOW;
            $description = 'Mục tiêu lợi nhuận hợp lý';
        }

        return [
            'level' => $level,
            'ratio' => $profitRatio,
            'description' => $description,
            'target_return' => round($profitRatio * 100, 1) . '%'
        ];
    }

    /**
     * Calculate strategy risk based on betting strategy
     */
    protected function calculateStrategyRisk(string $strategy, array $config): array
    {
        $strategyRisks = [
            'manual' => self::RISK_LOW,
            'auto_heatmap' => self::RISK_MEDIUM,
            'auto_streak' => self::RISK_MEDIUM,
            'auto_pattern' => self::RISK_HIGH,
            'auto_hybrid' => self::RISK_MEDIUM,
            'auto_fibonacci' => self::RISK_HIGH,
            'auto_martingale' => self::RISK_EXTREME
        ];

        $baseRisk = $strategyRisks[$strategy] ?? self::RISK_MEDIUM;

        // Adjust risk based on configuration
        $adjustedRisk = $this->adjustStrategyRisk($baseRisk, $strategy, $config);

        $descriptions = [
            self::RISK_LOW => 'Chiến lược an toàn, kiểm soát thủ công',
            self::RISK_MEDIUM => 'Chiến lược tự động với rủi ro có thể kiểm soát',
            self::RISK_HIGH => 'Chiến lược phức tạp với rủi ro cao',
            self::RISK_EXTREME => 'Chiến lược rủi ro cực kỳ cao, có thể mất toàn bộ vốn'
        ];

        return [
            'level' => $adjustedRisk,
            'base_strategy' => $strategy,
            'description' => $descriptions[$adjustedRisk],
            'config_analysis' => $this->analyzeStrategyConfig($strategy, $config)
        ];
    }

    /**
     * Calculate time risk based on campaign duration
     */
    protected function calculateTimeRisk(int $days, float $initialBalance, float $totalLossLimit): array
    {
        if ($days <= 7) {
            $level = self::RISK_HIGH;
            $description = 'Thời gian rất ngắn, áp lực cao';
        } elseif ($days <= 30) {
            $level = self::RISK_MEDIUM;
            $description = 'Thời gian ngắn hạn';
        } elseif ($days <= 90) {
            $level = self::RISK_LOW;
            $description = 'Thời gian trung hạn hợp lý';
        } else {
            $level = self::RISK_MEDIUM;
            $description = 'Thời gian dài hạn, cần kiên nhẫn';
        }

        // Adjust based on daily exposure
        if ($totalLossLimit > 0 && $initialBalance > 0) {
            $dailyExposure = ($totalLossLimit / $initialBalance) / $days;
            if ($dailyExposure > 0.02) { // More than 2% daily exposure
                $level = $this->increaseRiskLevel($level);
                $description .= ', tiếp xúc rủi ro hàng ngày cao';
            }
        }

        return [
            'level' => $level,
            'days' => $days,
            'description' => $description,
            'category' => $this->getTimeCategoryDescription($days)
        ];
    }

    /**
     * Calculate overall risk level from individual factors
     */
    protected function calculateOverallRisk(array $riskFactors): string
    {
        $riskWeights = [
            'loss' => 0.3,      // 30% weight
            'daily' => 0.25,    // 25% weight
            'profit' => 0.2,    // 20% weight
            'strategy' => 0.15, // 15% weight
            'time' => 0.1       // 10% weight
        ];

        $riskValues = [
            self::RISK_LOW => 1,
            self::RISK_MEDIUM => 2,
            self::RISK_HIGH => 3,
            self::RISK_EXTREME => 4
        ];

        $weightedSum = 0;
        foreach ($riskFactors as $factor => $risk) {
            $riskLevel = is_array($risk) ? $risk['level'] : $risk;
            $weightedSum += $riskValues[$riskLevel] * $riskWeights[$factor];
        }

        if ($weightedSum >= 3.5) return self::RISK_EXTREME;
        if ($weightedSum >= 2.5) return self::RISK_HIGH;
        if ($weightedSum >= 1.5) return self::RISK_MEDIUM;
        return self::RISK_LOW;
    }

    /**
     * Calculate numerical risk score (0-100)
     */
    protected function calculateRiskScore(string $overallRisk, array $riskFactors): int
    {
        $baseScores = [
            self::RISK_LOW => 25,
            self::RISK_MEDIUM => 50,
            self::RISK_HIGH => 75,
            self::RISK_EXTREME => 95
        ];

        $baseScore = $baseScores[$overallRisk];

        // Fine-tune based on individual factors
        $adjustments = 0;
        foreach ($riskFactors as $factor) {
            $level = is_array($factor) ? $factor['level'] : $factor;
            if ($level === self::RISK_EXTREME) $adjustments += 2;
            elseif ($level === self::RISK_HIGH) $adjustments += 1;
        }

        return min(100, max(0, $baseScore + $adjustments));
    }

    /**
     * Generate risk-based recommendations
     */
    protected function generateRecommendations(string $overallRisk, array $riskFactors, array $campaignData): array
    {
        $recommendations = [];

        // Loss risk recommendations
        if ($riskFactors['loss']['level'] === self::RISK_EXTREME) {
            $recommendations[] = 'Giảm giới hạn thua tổng xuống dưới 50% số dư ban đầu';
        }

        // Daily risk recommendations
        if ($riskFactors['daily']['level'] === self::RISK_HIGH) {
            $recommendations[] = 'Giảm giới hạn đặt cược và thua lỗ hàng ngày';
        }

        // Profit risk recommendations
        if ($riskFactors['profit']['level'] === self::RISK_EXTREME) {
            $recommendations[] = 'Đặt mục tiêu lợi nhuận thực tế hơn (dưới 300% vốn ban đầu)';
        }

        // Strategy recommendations
        if ($riskFactors['strategy']['level'] === self::RISK_EXTREME) {
            $recommendations[] = 'Tránh chiến lược Martingale hoặc giảm hệ số nhân';
            $recommendations[] = 'Cân nhắc chuyển sang chiến lược ít rủi ro hơn';
        }

        // General recommendations based on overall risk
        switch ($overallRisk) {
            case self::RISK_EXTREME:
                $recommendations[] = 'Xem xét lại toàn bộ chiến lược - rủi ro quá cao';
                $recommendations[] = 'Bắt đầu với số tiền nhỏ hơn để test';
                break;
            case self::RISK_HIGH:
                $recommendations[] = 'Thiết lập stop-loss chặt chẽ';
                $recommendations[] = 'Theo dõi sát sao trong giai đoạn đầu';
                break;
            case self::RISK_MEDIUM:
                $recommendations[] = 'Thiết lập các mốc đánh giá định kỳ';
                break;
        }

        return $recommendations;
    }

    /**
     * Generate warnings based on risk assessment
     */
    protected function generateWarnings(string $overallRisk, array $campaignData): array
    {
        $warnings = [];

        if ($overallRisk === self::RISK_EXTREME) {
            $warnings[] = [
                'type' => 'danger',
                'message' => 'CẢNH BÁO: Chiến dịch này có rủi ro cực kỳ cao. Bạn có thể mất toàn bộ số tiền đầu tư.'
            ];
        }

        if ($overallRisk === self::RISK_HIGH) {
            $warnings[] = [
                'type' => 'warning',
                'message' => 'Chú ý: Chiến dịch có rủi ro cao. Hãy chuẩn bị tâm lý cho khả năng thua lỗ lớn.'
            ];
        }

        // Specific warnings
        if (($campaignData['betting_strategy'] ?? '') === 'auto_martingale') {
            $warnings[] = [
                'type' => 'danger',
                'message' => 'Chiến lược Martingale có thể dẫn đến thua lỗ nhanh chóng khi gặp chuỗi thua dài.'
            ];
        }

        return $warnings;
    }

    /**
     * Suggest optimal limits based on risk assessment
     */
    protected function suggestOptimalLimits(float $initialBalance, string $riskLevel): array
    {
        $suggestions = [];

        switch ($riskLevel) {
            case self::RISK_LOW:
                $suggestions['total_loss_limit'] = $initialBalance * 0.15; // 15%
                $suggestions['daily_loss_limit'] = $initialBalance * 0.05; // 5%
                break;
            case self::RISK_MEDIUM:
                $suggestions['total_loss_limit'] = $initialBalance * 0.25; // 25%
                $suggestions['daily_loss_limit'] = $initialBalance * 0.08; // 8%
                break;
            case self::RISK_HIGH:
                $suggestions['total_loss_limit'] = $initialBalance * 0.35; // 35%
                $suggestions['daily_loss_limit'] = $initialBalance * 0.10; // 10%
                break;
            case self::RISK_EXTREME:
                $suggestions['total_loss_limit'] = $initialBalance * 0.10; // 10%
                $suggestions['daily_loss_limit'] = $initialBalance * 0.03; // 3%
                break;
        }

        $suggestions['daily_bet_limit'] = $suggestions['daily_loss_limit'] * 3; // Assume 33% win rate

        return $suggestions;
    }

    /**
     * Helper methods
     */
    protected function adjustStrategyRisk(string $baseRisk, string $strategy, array $config): string
    {
        if ($strategy === 'auto_martingale' && isset($config['martingale_multiplier'])) {
            if ($config['martingale_multiplier'] > 2.5) {
                return self::RISK_EXTREME;
            }
        }

        if ($strategy === 'auto_fibonacci' && isset($config['fibonacci_sequence'])) {
            if (max($config['fibonacci_sequence']) > 50) {
                return $this->increaseRiskLevel($baseRisk);
            }
        }

        return $baseRisk;
    }

    protected function increaseRiskLevel(string $currentLevel): string
    {
        $levels = [self::RISK_LOW, self::RISK_MEDIUM, self::RISK_HIGH, self::RISK_EXTREME];
        $currentIndex = array_search($currentLevel, $levels);
        return $levels[min($currentIndex + 1, count($levels) - 1)];
    }

    protected function analyzeStrategyConfig(string $strategy, array $config): array
    {
        $analysis = [];

        switch ($strategy) {
            case 'auto_heatmap':
                if (isset($config['min_confidence'])) {
                    $analysis['confidence'] = $config['min_confidence'] >= 0.8 ? 'cao' : 'trung bình';
                }
                break;
            case 'auto_martingale':
                if (isset($config['martingale_multiplier'])) {
                    $analysis['aggressiveness'] = $config['martingale_multiplier'] > 2.0 ? 'rất cao' : 'cao';
                }
                break;
        }

        return $analysis;
    }

    protected function getTimeCategoryDescription(int $days): string
    {
        if ($days <= 7) return 'Siêu ngắn hạn';
        if ($days <= 30) return 'Ngắn hạn';
        if ($days <= 90) return 'Trung hạn';
        if ($days <= 180) return 'Dài hạn';
        return 'Rất dài hạn';
    }

    /**
     * Public method to validate if campaign risk is acceptable
     */
    public function isRiskAcceptable(array $campaignData, string $userRiskTolerance = 'medium'): bool
    {
        $assessment = $this->assessCampaignRisk($campaignData);
        $overallRisk = $assessment['overall_risk'];

        $acceptableRisks = [
            'low' => [self::RISK_LOW],
            'medium' => [self::RISK_LOW, self::RISK_MEDIUM],
            'high' => [self::RISK_LOW, self::RISK_MEDIUM, self::RISK_HIGH],
            'extreme' => [self::RISK_LOW, self::RISK_MEDIUM, self::RISK_HIGH, self::RISK_EXTREME]
        ];

        return in_array($overallRisk, $acceptableRisks[$userRiskTolerance] ?? $acceptableRisks['medium']);
    }
}

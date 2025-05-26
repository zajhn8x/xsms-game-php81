<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignBet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    /**
     * Tạo mới một campaign (chiến dịch)
     * @param array $data Dữ liệu campaign (name, description, start_date, ...)
     * @return Campaign
     */
    public function create(array $data)
    {
        // Thiết lập mặc định cho initial_balance và current_balance nếu chưa có
        if (!isset($data['initial_balance'])) {
            $data['initial_balance'] = 1000000; // mặc định 1 triệu
        }
        if (!isset($data['current_balance'])) {
            $data['current_balance'] = $data['initial_balance'];
        }
        // Thiết lập mặc định cho bet_type và status
        $validBetTypes = ['manual', 'auto_heatmap', 'auto_streak', 'auto_rebound'];
        if (empty($data['bet_type']) || !in_array($data['bet_type'], $validBetTypes)) {
            $data['bet_type'] = 'manual';
        }
        $validStatus = ['waiting', 'active', 'running', 'paused', 'finished', 'completed'];
        if (empty($data['status']) || !in_array($data['status'], $validStatus)) {
            $data['status'] = 'active';
        }
        return Campaign::create($data);
    }

    /**
     * Thêm lượt đặt cược (bet) vào campaign
     * @param int $campaignId
     * @param array $betData (bet_date, bet_numbers, bet_amount, ...)
     * @return CampaignBet
     */
    public function addBet($campaignId, array $betData)
    {
        $betData['campaign_id'] = $campaignId;
        Log::info("data bet", ["data" => $betData]);
        return CampaignBet::create($betData);
    }

    /**
     * Tính toán hiệu quả chiến dịch: tổng tiền cược, tổng lợi nhuận, tỷ lệ thắng
     * @param int $campaignId
     * @return array
     */
    public function getReport($campaignId)
    {
        $bets = CampaignBet::where('campaign_id', $campaignId)->get();
        $totalBet = $bets->sum('bet_amount');
        $totalProfit = $bets->sum('profit');
        $totalWin = $bets->where('profit', '>', 0)->count();
        $totalCount = $bets->count();
        $winRate = $totalCount > 0 ? round($totalWin / $totalCount * 100, 2) : 0;
        return [
            'total_bet' => $totalBet,
            'total_profit' => $totalProfit,
            'total_bet_count' => $totalCount,
            'win_count' => $totalWin,
            'win_rate' => $winRate,
        ];
    }

    /**
     * Lấy danh sách bet của campaign
     * @param int $campaignId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBets($campaignId)
    {
        return CampaignBet::where('campaign_id', $campaignId)
            ->orderBy('bet_date', 'desc')
            ->get();
    }

    /**
     * Kết thúc campaign
     * @param int $campaignId
     * @return bool
     */
    public function finish($campaignId)
    {
        return Campaign::where('id', $campaignId)->update(['status' => 'finished', 'end_date' => now()]);
    }

    /**
     * Tạm dừng campaign
     * @param int $campaignId
     * @return bool
     */
    public function pause($campaignId)
    {
        return Campaign::where('id', $campaignId)->update(['status' => 'paused']);
    }

    /**
     * Lọc/tìm kiếm campaign theo trạng thái, thời gian, hiệu quả
     * @param array $filters (status, start_date, end_date, ...)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search($filters = [])
    {
        $query = Campaign::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Cập nhật thống kê campaign
     */
    public function updateCampaignStats($campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        $bets = $campaign->bets;

        $totalBet = $bets->sum('bet_amount');
        $totalProfit = $bets->sum('profit');
        $totalBetCount = $bets->count();
        $winCount = $bets->where('result', '>', 0)->count();
        $winRate = $totalBetCount > 0 ? round(($winCount / $totalBetCount) * 100, 2) : 0;

        $campaign->update([
            'total_bet' => $totalBet,
            'total_profit' => $totalProfit,
            'total_bet_count' => $totalBetCount,
            'win_rate' => $winRate
        ]);

        return $campaign;
    }

    /**
     * Kiểm tra kết quả bet
     */
    public function checkBetResult($bet)
    {
        // TODO: Implement logic kiểm tra kết quả xổ số
        // Tạm thời return mock data
        return [
            'hit_count' => rand(0, 1),
            'profit' => rand(-10000, 10000)
        ];
    }

    /**
     * Tạo mới campaign kèm user
     */
    public function createWithUser($user, array $data)
    {
        $data['user_id'] = $user->id;
        return $this->create($data);
    }
}
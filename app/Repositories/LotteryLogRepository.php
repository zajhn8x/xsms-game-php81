<?php

namespace App\Repositories;

use App\Models\LotteryLog;
use App\Contracts\Repositories\LotteryLogRepositoryInterface;
use Carbon\Carbon;

class LotteryLogRepository implements LotteryLogRepositoryInterface
{
    public function create(array $data)
    {
        return LotteryLog::create($data);
    }

    public function getUserLogs($userId, $days = 30)
    {
        return LotteryLog::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getSystemLogs($days = 7)
    {
        return LotteryLog::with('user')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function clearOldLogs($days = 90)
    {
        return LotteryLog::where('created_at', '<', Carbon::now()->subDays($days))
            ->delete();
    }
}
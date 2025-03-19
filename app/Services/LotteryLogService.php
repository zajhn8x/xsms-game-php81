<?php

namespace App\Services;

use App\Models\LotteryLog;
use Carbon\Carbon;

class LotteryLogService
{
    public function logAction($userId, $action, $data)
    {
        return LotteryLog::create([
            'user_id' => $userId,
            'action' => $action,
            'data' => $data,
            'created_at' => Carbon::now()
        ]);
    }

    public function getUserActivityLog($userId, $days = 30)
    {
        return LotteryLog::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getSystemActivityLog($days = 7)
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
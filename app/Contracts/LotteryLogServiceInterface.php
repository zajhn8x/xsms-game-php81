<?php

namespace App\Contracts;

interface LotteryLogServiceInterface
{
    public function logAction($userId, $action, $data);

    public function getUserActivityLog($userId, $days);

    public function getSystemActivityLog($days);

    public function clearOldLogs($days);
}

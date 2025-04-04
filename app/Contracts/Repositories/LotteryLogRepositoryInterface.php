<?php

namespace App\Contracts\Repositories;

interface LotteryLogRepositoryInterface
{
    public function create(array $data);

    public function getUserLogs($userId, $days);

    public function getSystemLogs($days);

    public function clearOldLogs($days);
}

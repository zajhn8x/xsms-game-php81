<?php

namespace App\Contracts;

interface LotteryBetServiceInterface
{
    public function placeBet($userId, $betData);
    public function processWinnings($resultId);
    public function getUserBetHistory($userId, $days);
    public function getBetStatistics($userId);
}

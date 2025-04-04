<?php

namespace App\Contracts;

interface LotteryResultServiceInterface
{
    public function getLatestResults($limit);

    public function getResultsByDateRange($startDate, $endDate);

    public function createResult(array $data);

    public function analyzeFrequency($days);
}

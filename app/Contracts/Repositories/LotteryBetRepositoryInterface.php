<?php

namespace App\Contracts\Repositories;

interface LotteryBetRepositoryInterface
{
    public function getUserBets($userId);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function findByDateRange($userId, $startDate, $endDate);
}
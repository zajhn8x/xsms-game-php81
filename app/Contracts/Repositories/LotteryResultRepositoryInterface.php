<?php

namespace App\Contracts\Repositories;

interface LotteryResultRepositoryInterface
{
    public function latest($limit);
    public function findByDateRange($startDate, $endDate);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
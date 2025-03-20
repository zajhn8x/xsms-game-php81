<?php

namespace App\Repositories;

use App\Models\LotteryResult;
use App\Contracts\Repositories\LotteryResultRepositoryInterface;
use Carbon\Carbon;

class LotteryResultRepository implements LotteryResultRepositoryInterface
{
    public function latest($limit)
    {
        return LotteryResult::orderBy('draw_date', 'desc')
            ->take($limit)
            ->get();
    }

    public function findByDateRange($startDate, $endDate)
    {
        return LotteryResult::whereBetween('draw_date', [
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        ])->get();
    }

    public function create(array $data)
    {
        return LotteryResult::create($data);
    }

    public function update($id, array $data)
    {
        $result = LotteryResult::findOrFail($id);
        $result->update($data);
        return $result;
    }

    public function delete($id)
    {
        return LotteryResult::destroy($id);
    }
}
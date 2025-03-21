<?php

namespace App\Services;

use App\Models\LotteryCauLoHit;
use Illuminate\Support\Facades\DB;

class LotteryCauLoHitService
{
    public function findConsecutiveHits($date, $days = 3)
    {
        $subqueries = [];
        for ($i = 0; $i < $days; $i++) {
            $alias = "h" . ($i + 1);
            $subqueries[] = "(SELECT cau_lo_id, ngay FROM lottery_cau_lo_hit WHERE ngay <= '$date') as $alias";
        }

        $joins = [];
        $conditions = [];
        for ($i = 1; $i < $days; $i++) {
            $current = "h" . ($i + 1);
            $prev = "h$i";
            $joins[] = "JOIN " . $subqueries[$i] . " ON $current.cau_lo_id = $prev.cau_lo_id";
            $conditions[] = "$current.ngay = DATE_SUB($prev.ngay, INTERVAL 1 DAY)";
        }

        $query = "
            SELECT h1.cau_lo_id, GROUP_CONCAT(h1.ngay ORDER BY h1.ngay ASC) as ngay_trung
            FROM " . $subqueries[0] . "
            " . implode(" ", $joins) . "
            WHERE  1 " . implode(" AND ", $conditions) . "
            GROUP BY h1.cau_lo_id
        ";
        return DB::select($query);
    }
}

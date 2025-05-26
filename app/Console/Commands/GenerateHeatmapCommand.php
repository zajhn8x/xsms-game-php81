<?php

namespace App\Console\Commands;

use App\Jobs\GenerateHeatmapJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateHeatmapCommand extends Command
{
    protected $signature = 'heatmap:generate
        {--from= : Ngày bắt đầu (Y-m-d)}
        {--to= : Ngày kết thúc (Y-m-d)}
        {--retry : Chạy lại cho các ngày đã có}
        {--delete : Xóa dữ liệu cũ trước khi tạo mới}';

    protected $description = 'Tạo heatmap records cho khoảng thời gian';

    public function handle()
    {
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->subDays(5);
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();

        if ($this->option('delete')) {
            \App\Models\HeatmapDailyRecord::whereBetween('date', [$from, $to])->delete();
        }

        $currentFrom = $from;
        $jobCount = 0;

        while ($currentFrom <= $to) {
            $currentTo = $currentFrom->copy()->addDays(2); // Thêm 2 ngày để có 3 ngày
            if ($currentTo > $to) {
                $currentTo = $to;
            }

            GenerateHeatmapJob::dispatch($currentFrom, $currentTo, $this->option('retry'));
            $jobCount++;

            $this->info(sprintf(
                'Đã dispatch job cho khoảng %s đến %s',
                $currentFrom->format('Y-m-d'),
                $currentTo->format('Y-m-d')
            ));

            $currentFrom = $currentTo->copy()->addDay();
        }

        $this->info(sprintf('Đã dispatch tổng cộng %d jobs!', $jobCount));
    }
}

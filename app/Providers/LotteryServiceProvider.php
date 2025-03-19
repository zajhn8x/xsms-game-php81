
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\LotteryResultServiceInterface;
use App\Contracts\LotteryBetServiceInterface;
use App\Contracts\LotteryLogServiceInterface;
use App\Services\LotteryResultService;
use App\Services\LotteryBetService;
use App\Services\LotteryLogService;

class LotteryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(LotteryResultServiceInterface::class, LotteryResultService::class);
        $this->app->bind(LotteryBetServiceInterface::class, LotteryBetService::class);
        $this->app->bind(LotteryLogServiceInterface::class, LotteryLogService::class);
    }

    public function boot()
    {
        //
    }
}


<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LotteryResultService;
use App\Contracts\LotteryResultServiceInterface;

class LotteryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(LotteryResultServiceInterface::class, LotteryResultService::class);
    }

    public function boot()
    {
        //
    }
}

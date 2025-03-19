<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LotteryService;
use App\Contracts\LotteryResultServiceInterface;

class LotteryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(LotteryResultServiceInterface::class, LotteryService::class);
    }

    public function boot()
    {
        //
    }
}

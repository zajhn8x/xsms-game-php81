
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LotteryService;

class LotteryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(LotteryService::class, function ($app) {
            return new LotteryService();
        });
    }

    public function boot()
    {
        //
    }
}


<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LotteryBetService;
use App\Repositories\LotteryBetRepository;

class LotteryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(LotteryBetService::class, function ($app) {
            return new LotteryBetService(
                $app->make(LotteryBetRepository::class)
            );
        });
    }

    public function boot()
    {
        //
    }
}

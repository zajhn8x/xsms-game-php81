<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Repositories\LotteryResultRepositoryInterface;
use App\Repositories\LotteryResultRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(LotteryResultRepositoryInterface::class, LotteryResultRepository::class);
        $this->app->bind(LotteryBetRepositoryInterface::class, LotteryBetRepository::class);
        $this->app->bind(LotteryLogRepositoryInterface::class, LotteryLogRepository::class);
    }

    public function boot()
    {
        //
    }
}
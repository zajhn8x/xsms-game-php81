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
    }

    public function boot()
    {
        //
    }
}
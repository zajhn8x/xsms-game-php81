<?php

namespace App\Providers;

use App\Services\LotteryResultService;
use Illuminate\Support\ServiceProvider;
use App\Contracts\LotteryResultServiceInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //$this->app->singleton(LotteryResultServiceInterface::class, LotteryResultService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * if($this->app->environment('production')) {
         * \URL::forceScheme('https');
         * }
         * \Illuminate\Http\Request::setTrustedProxies(
         * ['*'],
         * \Illuminate\Http\Request::HEADER_X_FORWARDED_ALL
         * );
         * */
    }
}

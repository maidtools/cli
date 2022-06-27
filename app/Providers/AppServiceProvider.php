<?php

namespace App\Providers;

use App\Repositories\UserAccessToken;
use Maid\Sdk\Contracts\UserTokenRepository;
use Maid\Sdk\Maid;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(Maid::class, function () {
            return (new Maid())
                ->withClientId('test');
        });

        $this->app->singleton(UserTokenRepository::class, UserAccessToken::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

<?php

namespace Modules\UserApi\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\UserApi\Contracts\AuthServiceInterface;
use Modules\UserApi\Services\AuthService;

class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
    }
}

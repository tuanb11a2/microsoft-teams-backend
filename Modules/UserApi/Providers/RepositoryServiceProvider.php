<?php

namespace Modules\UserApi\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\UserApi\Contracts\AuthRepoInterface;
use Modules\UserApi\Repositories\AuthRepo;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repositories.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(AuthRepoInterface::class, AuthRepo::class);
    }
}

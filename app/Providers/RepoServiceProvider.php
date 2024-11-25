<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;


class RepoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('App\Repositories\UserRepositoryInterface', 'App\Repositories\UserRepository');
        $this->app->bind('App\Repositories\FileRepositoryInterface', 'App\Repositories\FileRepository');

    }
}

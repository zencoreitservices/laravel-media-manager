<?php

namespace Zencoreitservices\MediaManager;

use Illuminate\Support\ServiceProvider;
use Zencoreitservices\MediaManager\Console\Commands\DeleteMediaWithNoFile;
use Zencoreitservices\MediaManager\Console\Commands\DeleteFilesWithNoMedia;

class MediaManagerProvider extends ServiceProvider
{
/**
    * Bootstrap any package services.
    *
    * @return void
    */
    public function boot()
    {
        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Publish config
        $this->publishes([
            __DIR__.'/../config/media-manager.php' => config_path('media-manager.php'),
        ], 'media-manager-config');

        // Console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                DeleteMediaWithNoFile::class,
                DeleteFilesWithNoMedia::class,
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Config merge
        $this->mergeConfigFrom(
            __DIR__.'/../config/media-manager.php', 'media-manager'
        );
    }
}
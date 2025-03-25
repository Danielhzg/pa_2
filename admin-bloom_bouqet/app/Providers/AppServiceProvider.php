<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Filesystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Explicitly bind the files cache driver
        $this->app->singleton('cache.stores.file', function ($app) {
            $config = $app['config']["cache.stores.file"];
            $diskConfig = [
                'driver' => 'local',
                'root' => $config['path'],
            ];
            $disk = $app['filesystem']->createLocalDriver($diskConfig);
            return new Repository(new FileStore($disk, $config['prefix'] ?? 'file'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

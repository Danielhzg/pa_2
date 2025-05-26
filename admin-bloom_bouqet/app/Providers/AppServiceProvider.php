<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;

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
        // Register the OrderHelper for Blade files
        Blade::directive('getOrderItems', function ($expression) {
            return "<?php echo json_encode(\\App\\Helpers\\OrderHelper::getOrderItems($expression)); ?>";
        });
        
        Blade::directive('getTotalItems', function ($expression) {
            return "<?php echo \\App\\Helpers\\OrderHelper::getTotalItems($expression); ?>";
        });
        
        Blade::directive('getOrderSubtotal', function ($expression) {
            return "<?php echo \\App\\Helpers\\OrderHelper::getSubtotal($expression); ?>";
        });

        // Add unread notification count to all views
        view()->composer('layouts.admin', function ($view) {
            if (auth()->guard('admin')->check()) {
                $unreadNotificationCount = \App\Models\Notification::where('admin_id', auth()->guard('admin')->id())
                    ->where('status', 'unread')
                    ->count();
                $view->with('unreadNotificationCount', $unreadNotificationCount);
            }
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use App\Services\WebSocketService;
use App\Services\NotificationService;

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
        
        // Register WebSocketService
        $this->app->singleton(WebSocketService::class, function ($app) {
            return new WebSocketService();
        });
        
        // Register NotificationService with WebSocketService dependency
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService($app->make(WebSocketService::class));
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
                // For admin, count all unread notifications (no admin_id filter)
                $unreadNotificationCount = \App\Models\Notification::where('is_read', false)
                    ->count();
                $view->with('unreadNotificationCount', $unreadNotificationCount);
            }
        });

        // Add unread notification count to notification component
        view()->composer('admin.components.notification_dropdown', function ($view) {
            if (auth()->guard('admin')->check()) {
                // For admin, count all unread notifications (no admin_id filter)
                $unreadNotificationCount = \App\Models\Notification::where('is_read', false)
                    ->count();
                $view->with('unreadNotificationCount', $unreadNotificationCount);
            } else {
                $view->with('unreadNotificationCount', 0);
            }
        });
    }
}

<?php


namespace Omnics\FileManagement;

use Illuminate\Support\ServiceProvider;

class FileManagementServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Load Routes
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

        // Load Migrations (if any)
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load Views (if any)
//        $this->loadViewsFrom(__DIR__ . '/resources/views', 'filemanagement');
    }
}

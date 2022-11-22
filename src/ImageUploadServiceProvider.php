<?php

namespace Treconyl\ImageUpload;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

use Treconyl\ImageUpload\Facades\ImageFacade;
use Treconyl\ImageUpload\Helpers\ImageHelper;

class ImageUploadServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
        $this->publishMigrations();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('ImageUpload', ImageFacade::class);

        $this->app->singleton('images_upload', function () {
            return new ImageHelper();
        });
    }

    #   --tag="config"
    private function publishConfig()
    {
        $path = $this->getConfigPath();
        $this->publishes([
            $path => config_path('image.php')
            # ...
        ], 'config');
    }

    #   --tag="migrations"
    private function publishMigrations()
    {
        $path = $this->getMigrationsPath();
        $this->publishes([
            $path => database_path('migrations')
            # ...
        ], 'migrations');
    }

    private function getConfigPath()
    {
        return __DIR__ . '/../../config/image.php';
    }

    private function getMigrationsPath()
    {
        return __DIR__ . '/../../database/migrations';
    }
}

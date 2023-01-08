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
        $this->publishes([
            __DIR__ . '/../config/image.php' => config_path('image.php')
            
        ], 'config');
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
}

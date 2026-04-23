<?php

declare(strict_types=1);

namespace Treconyl\ImagesUpload;

use Illuminate\Support\ServiceProvider;

class ImagesUploadServiceProvider extends ServiceProvider
{
    /**
     * Đăng ký các dịch vụ và binding.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/image-upload.php', 'image-upload');

        $this->app->singleton('images-upload', fn () => new ImageUpload());
    }

    /**
     * Khởi động package (publish config).
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/image-upload.php' => config_path('image-upload.php'),
            ], 'image-upload-config');
        }
    }
}

<?php

namespace Treconyl\ImagesUpload;

use Illuminate\Support\ServiceProvider;

class ImagesUploadServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Đăng ký các dịch vụ
        $this->app->singleton('images-upload', function ($app) {
            return new ImageUpload();
        });

        // Đăng ký facade
        $this->app->bind('images-upload', function ($app) {
            return new \Treconyl\ImagesUpload\Facades\ImageUpload;
        });
    }

    public function boot()
    {
        // Xuất tệp cấu hình
        $this->publishes([
            __DIR__ . '/../config/image-upload.php' => config_path('image-upload.php'),
        ]);
    }
}
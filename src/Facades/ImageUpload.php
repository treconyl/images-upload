<?php

namespace Treconyl\ImagesUpload\Facades;

use Illuminate\Support\Facades\Facade;

class ImageUpload extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'images-upload'; // Tên dịch vụ trong container
    }
}
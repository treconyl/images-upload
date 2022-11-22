<?php

namespace Treconyl\ImageUpload\Facades;

use Illuminate\Support\Facades\Facade;

class ImageFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @see \Treconyl\Image
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'images_upload';
    }
}

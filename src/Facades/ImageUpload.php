<?php

declare(strict_types=1);

namespace Treconyl\ImagesUpload\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Treconyl\ImagesUpload\ImageUpload file(\Illuminate\Http\Request $request, string $input, int $quantity = 100, bool $filename_encoding = true, bool $overwrite = false)
 * @method static \Treconyl\ImagesUpload\ImageUpload folder(string $name = 'default', bool $timestamp = true, string $disk = 'public')
 * @method static \Treconyl\ImagesUpload\ImageUpload convert(?string $convert = null)
 * @method static \Treconyl\ImagesUpload\ImageUpload allowedMimetypes(array $types = [])
 * @method static \Treconyl\ImagesUpload\ImageUpload thumbnails(?array $thumbnails = null)
 * @method static string|array store()
 *
 * @see \Treconyl\ImagesUpload\ImageUpload
 */
class ImageUpload extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'images-upload';
    }
}

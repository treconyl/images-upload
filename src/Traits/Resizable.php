<?php

declare(strict_types=1);

namespace Treconyl\ImagesUpload\Traits;

use Illuminate\Support\Str;

trait Resizable
{
    /**
     * Trả về URL thumbnail của thuộc tính hình ảnh trên model.
     *
     * @param  string  $type       Loại thumbnail (vd: 'lg', 'md', 'sm').
     * @param  string  $attribute  Tên thuộc tính chứa tên tệp hình ảnh.
     */
    public function thumbnail(string $type, string $attribute = 'image'): string
    {
        $image = $this->getAttribute($attribute);

        if (empty($image) || ! is_string($image)) {
            return '';
        }

        return $this->getThumbnail($image, $type);
    }

    /**
     * Tạo URL thumbnail dựa trên tên tệp gốc và loại thumbnail.
     */
    public function getThumbnail(string $image, string $type): string
    {
        $ext = pathinfo($image, PATHINFO_EXTENSION);
        $name = $ext !== ''
            ? Str::replaceLast('.' . $ext, '', $image)
            : $image;

        $directory = dirname($name);
        $basename = basename($name);

        $directory = $directory === '.' ? '' : $directory . '/';

        return $directory . $type . '/' . $basename . ($ext !== '' ? '.' . $ext : '');
    }
}

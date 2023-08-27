<?php

namespace Treconyl\ImageUpload\Helpers;

use Exception;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Intervention\Image\Constraint;

class ImageHelper
{
    private $source;
    private $resize;
    private $extension;
    private $thumbnails;
    private $watermask;
    private $allowed_mimetypes;

    public function __construct()
    {
        $this->resize       = config('image.resize');
        $this->extension    = '';
        $this->thumbnails   = [];
        $this->watermask    = [];
        $this->allowed_mimetypes = config('image.allowed_mimetypes');
    }

    public function file($source, string $filename)
    {
        $this->source = $source->file($filename);

        return $this;
    }

    public function thumbnails($thumbnails = [])
    {
        $this->thumbnails = count($thumbnails) > 0 ? $thumbnails : config('image.thumbnails');

        return $this;
    }

    public function extension($format = null)
    {
        $this->extension = $format;

        return $this;
    }

    public function resize(
        $width      = null,
        $height     = null,
        $quantity   = null,
        $upsize     = null
    ) {
        $this->resize['width']      = $width ?? config('image.resize.width');
        $this->resize['height']     = $height ?? config('image.resize.height');
        $this->resize['quantity']   = $quantity ?? config('image.resize.quantity');
        $this->resize['upsize']     = $upsize ?? config('image.resize.upsize');

        return $this;
    }

    public function watermask(
        $public_path  = 'watermask/default.png',
        $position     = 'bottom-left',
        $x            = 0,
        $y            = 0
    ) {
        $this->watermask['public_path']    = public_path($public_path);
        $this->watermask['position']       = $position;
        $this->watermask['x']              = $x;
        $this->watermask['y']              = $y;

        return $this;
    }

    public function allowed_mimetypes($mimetypes = [])
    {
        $this->allowed_mimetypes = $mimetypes;

        return $this;
    }

    ## START lưu ảnh và trả về url
    public function store($folder = 'default')
    {
        $file               = $this->source;
        $path               = $folder . '/' . date('F') . date('Y') . '/';
        $checkExtension     = $this->extension != null ? $this->extension : $file->getClientOriginalExtension();

        #get url image
        $url = $this->url($file, $folder);

        if (is_array($file)) {
            foreach ($file as $key => $item) {
                $this->save_disk($item, $path, $folder);
            }
        } else {
            $this->save_disk($file, $path, $folder);
        }

        #nếu thêm thumbnails (các hình thu nhỏ)
        $this->resize_thumbnails($path, $checkExtension);

        return $url;
    }
    public function save_disk($item, $path, $folder)
    {
        $file               = $item;
        $resize             = $this->resize;
        $watermask          = $this->watermask;
        $filename_counter   = 1;
        $allowed_mimetypes  = $this->allowed_mimetypes != null ? $this->allowed_mimetypes : config('image.allowed_mimetypes');

        # Đảm bảo rằng tên tệp không tồn tại, nếu có, hãy thêm một số vào cuối 1, 2, 3, v.v.
        $filename           = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $checkExtension     = $this->extension != null ? $this->extension : $file->getClientOriginalExtension();
        while (Storage::disk('public')->exists($path . $filename . '.' . $checkExtension)) :
            $filename = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . (string) ($filename_counter++);
        endwhile;
        $fullPath = $path . $filename . '.' . $checkExtension;
        # end

        

        #check extenstion [jpg, png, git, jpeg ... ]
        if (!in_array($file->guessClientExtension(), $allowed_mimetypes)) {
            return new Exception('Unsupported image format');
        }

        if ($file->guessClientExtension() == 'pdf'){
            $name   = $filename . '.' . $checkExtension;
            Storage::disk('public')->putFileAs($path, $file, $name);
        } else {
                $image = Image::make($file)->resize($resize['width'], $resize['height'], function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize(); #ngăn chặn tăng kích thước có thể xảy ra
            });
            if ($file->guessClientExtension() !== 'gif') :
                $image->orientate();
            endif;
    
            #nếu có ảnh watermask
            if (count($watermask) > 0) :
                $image->insert($watermask);
            endif;
    
            $image->encode($checkExtension, $resize['quantity']);
    
            #di chuyển tệp đã tải lên từ tạm thời sang thư mục tải lên
            Storage::disk('public')->put($fullPath, (string) $image, 'public');
        }
    }
    ## END

    ## START thêm các ảnh nhỏ hơn
    public function resize_thumbnails($path, $checkExtension)
    {
        if (count($this->thumbnails) < 1) {
            return false;
        }

        $file = $this->source;
        
        if (is_array($file)) {
            foreach ($file as $item) {
                $filename           = basename(pathinfo($item->getClientOriginalName(), PATHINFO_FILENAME));
                $this->loop_thumbnails($item, $path, $filename, $checkExtension);
            }
        } else {
            $filename           = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

            $this->loop_thumbnails($file, $path, $filename, $checkExtension);
        }
    }
    public function loop_thumbnails($file, $path, $filename, $checkExtension)
    {
        $thumbnails         = $this->thumbnails;
        $filename_counter   = 1;
        $image              = Image::make($file);
        $watermask          = $this->watermask;
        $resize             = $this->resize;

        #nếu có ảnh watermask
        if (count($watermask) > 0) :
            $image->insert($watermask);
        endif;

        foreach ($thumbnails as $key => $value) {
            # Đảm bảo rằng tên tệp không tồn tại, nếu có, hãy thêm một số vào cuối 1, 2, 3, v.v.
            while (Storage::disk('public')->exists($path . $filename . '-' . $key . '.' . $checkExtension)) {
                $filename = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . (string) ($filename_counter++);
            }

            foreach ($value as $zip => $property) {
                if (is_array($property)) {
                    if ($zip == 'fit') {
                        $image->fit(
                            $property["width"],
                            ($property["height"] ?? null),
                            function ($constraint) {
                                $constraint->aspectRatio();
                            },
                            ($property["position"] ?? 'center')
                        );
                    } elseif ($zip == 'crop') {
                        $image->crop(
                            $property["width"],
                            $property["height"],
                            ($property["x"] ?? null),
                            ($property["y"] ?? null)
                        );
                    } elseif ($zip == 'resize') {
                        $image->resize(
                            $property["width"],
                            ($property["height"] ?? null),
                            function ($constraint) use ($resize) {
                                $constraint->aspectRatio();
                                if (!($resize["upsize"] ?? true)) {
                                    $constraint->upsize();
                                }
                            }
                        );
                    }
                }
            }

            $fullPathImage = $path . $filename . '-' . $key . '.' . $checkExtension;

            $image->encode($checkExtension, $resize['quantity']);

            # di chuyển tệp đã tải lên từ tạm thời sang thư mục tải lên
            Storage::disk('public')->put($fullPathImage, (string) $image, 'public');
        }
    }
    ## END

    ## Trả về url ảnh
    public function url($file, $folder)
    {
        $path = $folder . '/' . date('F') . date('Y') . '/';

        if (is_array($file)) {

            $fullPath       = [];

            foreach ($file as $item) {
                $filename           = basename(pathinfo($item->getClientOriginalName(), PATHINFO_FILENAME));
                $filename_counter   = 1;
                $checkExtension     = $this->extension != null ? $this->extension : $item->getClientOriginalExtension();

                while (Storage::disk('public')->exists($path . $filename . '.' . $checkExtension)) {
                    $filename = basename(pathinfo($item->getClientOriginalName(), PATHINFO_FILENAME)) . (string) ($filename_counter++);
                }

                $fullPath[] .= $path . $filename . '.' . $checkExtension;
            }

            return json_encode($fullPath);
        } else {

            $checkExtension     = $this->extension != null ? $this->extension : $file->getClientOriginalExtension();
            $filename           = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $filename_counter   = 1;

            while (Storage::disk('public')->exists($path . $filename . '.' . $checkExtension)) {
                $filename = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . (string) ($filename_counter++);
            }

            $fullPath = $path . $filename . '.' . $checkExtension;

            return $fullPath;
        }
    }

    /**
     * @return string
     */
    function image($file, $default = '')
    {
        if (!empty($file)) :
            return str_replace('\\', '/', Storage::disk('public')->url($file));
        endif;

        return $default;
    }

    public function getPath($folder)
    {
        $file       = $this->source;
        $path       = $folder . '/' . date('F') . date('Y') . '/';

        if (is_array($file)) {
            $fullPath       = [];

            foreach ($file as $item) {
                $filename           = basename(pathinfo($item->getClientOriginalName(), PATHINFO_FILENAME));
                $filename_counter   = 1;
                $checkExtension     = $this->extension != null ? $this->extension : $item->getClientOriginalExtension();

                while (Storage::disk('public')->exists($path . $filename . '.' . $checkExtension)) {
                    $filename = basename(pathinfo($item->getClientOriginalName(), PATHINFO_FILENAME)) . (string) ($filename_counter++);
                }

                $fullPath[] .= $path . $filename . '.' . $checkExtension;
            }

            return json_encode($fullPath);
        } else {

            $checkExtension     = $this->extension != null ? $this->extension : $file->getClientOriginalExtension();
            $filename           = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $filename_counter   = 1;

            while (Storage::disk('public')->exists($path . $filename . '.' . $checkExtension)) {
                $filename = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . (string) ($filename_counter++);
            }

            $fullPath = $path . $filename . '.' . $checkExtension;

            return $fullPath;
        }
    }
}

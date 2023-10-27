<?php

namespace Treconyl\ImageUpload\Helpers;

use Exception;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Intervention\Image\Constraint;

class ImageHelper
{
    private $disk;
    private $folder;
    private $source;
    private $resize;
    private $extension;
    private $thumbnails;
    private $watermask;
    private $allowed_mimetypes;
    private $visibility;

    public function __construct()
    {
        $this->disk         = 'public';
        $this->visibility   = 'public';
        $this->folder       = config('image.folder', date('F') . date('Y'));
        $this->resize       = config('image.resize');
        $this->extension    = '';
        $this->thumbnails   = [];
        $this->watermask    = [];
        $this->allowed_mimetypes = config('image.allowed_mimetypes');
    }

    /**
     * Tuỳ chỉnh ổ đĩa lưu trữ
     */
    public function disk(string $disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Khả năng hiển thị tệp
     */
    public function visibility(string $visibility = 'public')
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Khả năng hiển thị tệp
     */
    public function file($source, string $filename)
    {
        $this->source = $source->file($filename);

        return $this;
    }

    /**
     * Tùy chỉnh thư mục tải lên 
     */
    public function folder(string $folder, bool $folderDate = true)
    {
        $path = date('F') . date('Y');

        if ($folderDate === true) {
            $this->folder = $folder . '/' . $path;
        } else {
            $this->folder = $folder;
        }

        return $this;
    }

    /**
     * Tùy chỉnh hình thu nhỏ của ảnh
     */
    public function thumbnails($thumbnails = [])
    {
        $this->thumbnails = count($thumbnails) > 0 ? $thumbnails : config('image.thumbnails');

        return $this;
    }

    /**
     * Tùy chỉnh định dạng lưu trữ
     */
    public function extension($extension = null)
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Tùy chỉnh tối ưu kích thước
     */
    public function resize(
        $width      = 1600,
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

    /**
     * Gán ảnh mặt nạ tùy chỉnh
     */
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

    /**
     * Tùy chỉnh định dạng được cho phép
     */
    public function allowed_mimetypes($mimetypes = [])
    {
        $this->allowed_mimetypes = $mimetypes;

        return $this;
    }

    ## START lưu ảnh và trả về url
    public function store()
    {
        try {
            $file      = $this->source;
            $path      = $this->folder;
            $extension = $this->extension ?? $file->getClientOriginalExtension();

            if (!$file) {
                return new Exception('No input received');
            }

            #get url image
            $url = $this->url($file, $path);

            if (is_array($file)) {
                foreach ($file as $item) {
                    $this->save_disk($item, $path);
                }
            } else {
                $this->save_disk($file, $path);
            }

            #nếu thêm thumbnails (các hình thu nhỏ)
            $this->resize_thumbnails($path, $extension);

            return $url;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
    public function save_disk($file, $path)
    {
        $extension          = $this->extension != null ? $this->extension : $file->guessClientExtension();
        $storage            = Storage::disk($this->disk);
        $resize             = $this->resize;
        $watermask          = $this->watermask;
        $allowed_mimetypes  = $this->allowed_mimetypes != null ? $this->allowed_mimetypes : config('image.allowed_mimetypes');
        $filename_counter   = 1;

        #check extenstion [jpg, png, git, jpeg ... ]
        if (!in_array($file->guessClientExtension(), $allowed_mimetypes)) {
            return new Exception('Unsupported image format');
        }
        
        # Đảm bảo rằng tên tệp không tồn tại, nếu có, hãy thêm một số vào cuối 1, 2, 3, v.v.
        $filename  = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        
        while ($storage->exists($path  . '/' . $filename . '.' . $extension)) :
            $filename = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . (string) ($filename_counter++);
        endwhile;
        $fullPath = $path . '/' . $filename . '.' . $extension;
        # end

        if ($file->guessClientExtension() == 'pdf'){
            $name   = $filename . '.' . $extension;
            $storage->putFileAs($path, $file, $name);
        } else {
            $image = Image::make($file)->resize($resize['width'], $resize['height'], function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize(); #ngăn chặn tăng kích thước có thể xảy ra
            });
            if ($file->guessClientExtension() !== 'gif'){
                $image->orientate();
            }
    
            #nếu có ảnh watermask
            if (count($watermask) > 0) {
                $image->insert($watermask);
            }
    
            $image->encode($extension, $resize['quantity']);
    
            #di chuyển tệp đã tải lên từ tạm thời sang thư mục tải lên
            $storage->put($fullPath, (string) $image, $this->visibility);
        }
    }
    ## END

    ## START thêm các ảnh nhỏ hơn
    public function resize_thumbnails($path, $extension)
    {
        if (count($this->thumbnails) < 1) {
            return false;
        }

        $file = $this->source;
        
        if (is_array($file)) {
            foreach ($file as $item) {
                $filename           = basename(pathinfo($item->getClientOriginalName(), PATHINFO_FILENAME));
                $this->loop_thumbnails($item, $path, $filename, $extension);
            }
        } else {
            $filename           = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

            $this->loop_thumbnails($file, $path, $filename, $extension);
        }
    }
    public function loop_thumbnails($file, $path, $filename, $extension)
    {
        $storage            = Storage::disk($this->disk);
        $image              = Image::make($file);
        $thumbnails         = $this->thumbnails;
        $watermask          = $this->watermask;
        $resize             = $this->resize;
        $filename_counter   = 1;

        #nếu có ảnh watermask
        if (count($watermask) > 0) :
            $image->insert($watermask);
        endif;

        foreach ($thumbnails as $key => $value) {
            # Đảm bảo rằng tên tệp không tồn tại, nếu có, hãy thêm một số vào cuối 1, 2, 3, v.v.
            while ($storage->exists($path . '/' . $filename . '-' . $key . '.' . $extension)) {
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

            $fullPathImage = $path . '/' . $filename . '-' . $key . '.' . $extension;

            $image->encode($extension, $resize['quantity']);

            # di chuyển tệp đã tải lên từ tạm thời sang thư mục tải lên
            $storage->put($fullPathImage, (string) $image, $this->visibility);
        }
    }
    ## END

    ## Trả về url ảnh
    public function url($file, $folder)
    {
        $storage = Storage::disk($this->disk);

        if (is_array($file)) {
            $fullPath       = [];

            foreach ($file as $item) {
                $filename           = basename(pathinfo($item->getClientOriginalName(), PATHINFO_FILENAME));
                $filename_counter   = 1;
                $extension          = $this->extension != null ? $this->extension : $file->guessClientExtension();
                $path               = $folder . '/' . $filename . '.' . $extension;

                while ($storage->exists($path)) {
                    $path = $folder . '/' . $filename . (string) ($filename_counter) . '.' . $extension;
                    $filename_counter++;
                }

                $fullPath[] .= $path;
            }

            return json_encode($fullPath);
        }

        if(!is_array($file)){
            $extension          = $this->extension != null ? $this->extension : $file->guessClientExtension();

            $filename           = basename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $filename_counter   = 1;
            $path               = $folder . '/' . $filename . '.' . $extension;
            
            while ($storage->exists($path)) {
                $path = $folder . '/' . $filename . (string) ($filename_counter) . '.' . $extension;
                $filename_counter++;
            }
            
            return $path;
        }
    }

    /**
     * Lấy hình ảnh từ storage
     * @return string
     */
    function image($file, $default = '')
    {
        if (!empty($file)) {
            return str_replace('\\', '/', Storage::disk($this->disk)->url($file));
        }

        return $default;
    }
}

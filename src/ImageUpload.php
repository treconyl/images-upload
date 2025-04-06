<?php

namespace Treconyl\ImagesUpload;

use Exception;
use Throwable;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageUpload
{
    protected $file = [];
    protected $filename_encoding = true;
    protected $quantity;
    protected $folder;
    protected $disk;
    protected $overwrite;
    protected $convert = null;
    protected $allowed_mimetypes;
    protected $thumbnails = [];

    public function __construct()
    {
        $this->allowed_mimetypes = config('image-upload.allowed_mimetypes', []);
    }

    /**
     * Khởi tạo đối tượng ImageUpload với các tham số từ yêu cầu.
     *
     * @param \Illuminate\Http\Request $request Yêu cầu HTTP chứa tệp tin tải lên.
     * @param string $input Tên input chứa tệp tin tải lên.
     * @param int $quantity Chất lượng ảnh khi lưu (mặc định là 100), chỉ áp dụng ['jpeg', 'jpg', 'webp'] còn PNG là định dạng ảnh không nén mất dữ liệu (lossless).
     * @param bool $filename_encoding Mã hóa tên tệp hay không (mặc định là true) Ảnh đại diện.png => anh-dai-den.png.
     * @param bool $overwrite Đè lên tệp nếu trùng tên hay không (mặc định là false).
     */
    public static function file($request, $input, $quantity = 100, $filename_encoding = true, $overwrite = false)
    {
        $instance = new self();
        
        // Lấy tất cả file từ yêu cầu và đảm bảo là mảng
        $files = $request->file($input);
        $instance->file = is_array($files) ? $files : [$files];
        // Thay đổi chất lượng ảnh khi lưu
        $instance->quantity = $quantity;
        // Đè lên tệp nếu trùng tên file
        $instance->overwrite = $overwrite;
        // Mã hóa tên tệp
        $instance->filename_encoding = $filename_encoding;

        return $instance;
    }

    /**
     * Đặt tên thư mục để lưu trữ tệp.
     *
     * @param string $name Tên thư mục (mặc định là 'default').
     * @param bool $timestamp Nếu true, thêm dấu thời gian vào tên thư mục.
     * @param string $disk Disk lưu trữ (mặc định là 'public').
     */
    public function folder($name = 'default', $timestamp = true, $disk = 'public')
    {
        $this->disk = $disk;
    
        $folderName = $name ?: 'default';
    
        $timestamp ? $this->folder = $folderName . '/' . date('F') . date('Y') : $this->folder = $folderName;
    
        return $this;
    }

    /**
     * Thiết lập đuôi file lưu trữ.
     *
     * @param string|null $convert Chuyển đổi tệp ảnh sang định dạng khác
     * @return self Trả về đối tượng ImageUpload.
     */
    public function convert($convert = null)
    {
        $this->convert = $convert;
        
        return $this;
    }

    /**
     * Đặt các loại MIME hợp lệ.
     * Nếu mảng không được truyền vào hoặc mảng rỗng, sử dụng cấu hình mặc định.
     *
     * @param array $types
     * @return $this
     */
    public function allowedMimetypes(array $types = [])
    {
        // Kiểm tra nếu $types là mảng không rỗng
        if (!empty($types) && is_array($types)) {
            $this->allowed_mimetypes = $types;
        } else {
            $this->allowed_mimetypes = cache('image-upload.allowed_mimetypes', []);
        }

        return $this;
    }
    
    /**
     * Kiểm tra định dạng tệp trước khi lưu.
     *
     * @param \Illuminate\Http\UploadedFile $file Tệp tải lên.
     * @return void
     * @throws Exception Nếu định dạng tệp không hợp lệ.
     */
    protected function validateFileMimetype($file)
    {
        $mimetype = $file->getClientOriginalExtension();
        
        if (!in_array($mimetype, $this->allowed_mimetypes)) {
            throw new Exception("Loại tệp không hợp lệ: $mimetype. Các loại tệp được phép: " . implode(', ', $this->allowed_mimetypes));
        }
    }

    /**
     * Thiết lập các kích thước thumbnail.
     *
     * @param array $thumbnails Mảng chứa các kích thước thumbnail.
     * @return self Trả về đối tượng ImageUpload.
     */
    public function thumbnails($thumbnails = null)
    {
        // Lấy từ config nếu không có tham số
        $this->thumbnails = $thumbnails ?: config('image-upload.resize', []);
        return $this;
    }

    /**
     * Thực hiện việc lưu trữ file.
     *
     * @return array|string URL của file đã lưu hoặc mảng chứa URL nếu có nhiều file.
     */
    public function store()
    {
        $urls = [];
        $manager = new ImageManager(new Driver());

        try {
            foreach ($this->file as $file) {
                // Kiểm tra định dạng tệp trước khi xử lý
                $this->validateFileMimetype($file);

                $extension = strtolower($file->getClientOriginalExtension());
                
                // Nếu là file video/audio thì chỉ lưu vào thư mục, không xử lý bằng Intervention
                if (in_array($extension, ['mp4', 'mov', 'avi', 'wmv', 'mkv', 'flv', 'mp3', 'wav', 'ogg', 'aac'])) {
                    $filename = $this->generateFilename($file, $extension);

                    Storage::disk($this->disk)->putFileAs(
                        $this->folder,
                        $file,
                        $filename
                    );

                    $urls[] = '/storage/' . ltrim($this->folder . '/' . $filename, '/');
                    continue;
                }

                $image = $manager->read($file->getPathname());
                
                // Chuyển đổi định dạng nếu cần
                if ($this->convert) {
                    $image = $image->encodeByExtension($this->convert, progressive: true, quality: $this->quantity);
                    $extension = $this->convert;
                } else {
                    $image = $image->encodeByExtension($file->getClientOriginalExtension(), progressive: true, quality: $this->quantity);
                    $extension = $file->getClientOriginalExtension();
                }

                // Tạo tên tệp với phần mở rộng thích hợp
                $filename = $this->generateFilename($file, $extension);

                // Lưu tệp chính
                Storage::disk($this->disk)->put(
                    (string) $this->folder . '/' . $filename,
                    (string) $image
                );

                foreach ($this->thumbnails as $thumbKey => $thumbOptions) {
                    $thumbImage = $manager->read($file->getPathname());
                    
                    // Áp dụng các tùy chọn resize cho thumbnail
                    if (isset($thumbOptions['resize'])) {
                        $width = $thumbOptions['resize']['width'] ?? null;
                        $height = $thumbOptions['resize']['height'] ?? null;
                        $upsize = $thumbOptions['resize']['upsize'] ?? false;
                        
                        if ($upsize) {
                            $thumbImage->scaleDown($width, $height);
                        } else {
                            $thumbImage->scale($width, $height);
                        }
                    }

                    $url = $this->generateThumbnailFilename($filename, $thumbKey, $extension);

                    // Lưu tệp thumbnail
                    Storage::disk($this->disk)->put(
                        $url,
                        (string) $thumbImage->encodeByExtension($this->convert ? $this->convert : $file->getClientOriginalExtension(), $this->quantity)
                    );
                }

                $urls[] = '/storage/' . ltrim($this->folder . '/' . $filename, '/');
            }
        } catch (Throwable $exception) {
            throw new Exception('Không thể tải lên ảnh: ' . $exception->getMessage());
        }

        return count($urls) > 1 ? $urls : $urls[0];
    }

    /**
     * Tạo tên tệp duy nhất.
     *
     * @param \Illuminate\Http\UploadedFile $file Tệp tải lên.
     * @param string $extension Phần mở rộng của tệp.
     * @return string Tên tệp duy nhất.
     */
    protected function generateFilename($file, $extension)
    {
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        if ($this->filename_encoding) {
            $filename = Str::slug($filename, '-');
        }

        if ($this->overwrite) {
            return $filename . '.' . $extension;
        }

        $originalFilename = $filename;
        $count = 1;
        
        while (Storage::disk($this->disk)->exists($this->folder . '/' . $filename . '.' . $extension)) {
            $filename = $originalFilename . '-' . $count++;
        }

        return $filename . '.' . $extension;
    }

    /**
     * Tạo tên tệp cho thumbnail.
     *
     * @param string $filename Tên tệp gốc.
     * @param string $thumbKey Key của thumbnail.
     * @param string $extension Phần mở rộng của tệp.
     * @return string Tên tệp thumbnail.
     */
    protected function generateThumbnailFilename($filename, $thumbKey, $extension)
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        if ($this->filename_encoding) {
            $name = Str::slug($name, '-');
            $url = (string) $this->folder . '/' . $thumbKey . '/' . $name . '.' . $extension;
        } else {
            $url = (string) $this->folder . '/' . $name . '-' . $thumbKey . '.' . $extension;
        }

        if ($this->overwrite) {
            return $url;
        }

        $count = 1;
        while (Storage::disk($this->disk)->exists($url)) {
            if ($this->filename_encoding) {
                $url = $this->folder . '/' . $thumbKey . '/' . $name . '-' . $count++ . '.' . $extension;
            } else {
                $url = $this->folder . '/' . $name . '-' . $thumbKey . '-' . $count++ . '.' . $extension;
            }
        }
        
        return $url;
    }

    /**
     * Ghi thông tin đối tượng ra log.
     *
     * @return void
     */
    public function log()
    {
        return $this;
    }
}
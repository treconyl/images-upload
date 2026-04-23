<?php

declare(strict_types=1);

namespace Treconyl\ImagesUpload;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageUpload
{
    /**
     * Các định dạng media không xử lý qua Intervention, chỉ lưu trực tiếp.
     */
    protected const MEDIA_EXTENSIONS = [
        'mp4', 'mov', 'avi', 'wmv', 'mkv', 'flv',
        'mp3', 'wav', 'ogg', 'aac',
    ];

    /** @var array<int, UploadedFile> */
    protected array $file = [];

    protected bool $filename_encoding = true;
    protected int $quantity = 100;
    protected string $folder = 'default';
    protected string $disk = 'public';
    protected bool $overwrite = false;
    protected ?string $convert = null;

    /** @var array<int, string> */
    protected array $allowed_mimetypes = [];

    /** @var array<string, array<string, mixed>> */
    protected array $thumbnails = [];

    public function __construct()
    {
        $this->allowed_mimetypes = (array) config('image-upload.allowed_mimetypes', []);
    }

    /**
     * Khởi tạo đối tượng ImageUpload với các tham số từ yêu cầu.
     *
     * @param  Request  $request            Yêu cầu HTTP chứa tệp tin tải lên.
     * @param  string   $input              Tên input chứa tệp tin tải lên.
     * @param  int      $quantity           Chất lượng ảnh khi lưu (1-100). Chỉ áp dụng cho jpeg/jpg/webp.
     * @param  bool     $filename_encoding  Mã hoá tên tệp dạng slug. Ảnh đại diện.png => anh-dai-dien.png.
     * @param  bool     $overwrite          Ghi đè tệp nếu trùng tên.
     */
    public static function file(
        Request $request,
        string $input,
        int $quantity = 100,
        bool $filename_encoding = true,
        bool $overwrite = false
    ): static {
        $instance = new static();

        $files = $request->file($input);

        $instance->file = match (true) {
            $files === null => [],
            is_array($files) => array_values($files),
            default => [$files],
        };

        $instance->quantity = $quantity;
        $instance->filename_encoding = $filename_encoding;
        $instance->overwrite = $overwrite;

        return $instance;
    }

    /**
     * Đặt tên thư mục để lưu trữ tệp.
     */
    public function folder(string $name = 'default', bool $timestamp = true, string $disk = 'public'): static
    {
        $this->disk = $disk;

        $folderName = $name !== '' ? $name : 'default';

        $this->folder = $timestamp
            ? $folderName . '/' . date('FY')
            : $folderName;

        return $this;
    }

    /**
     * Chuyển đổi định dạng ảnh đầu ra (vd: 'webp').
     */
    public function convert(?string $convert = null): static
    {
        $this->convert = $convert;

        return $this;
    }

    /**
     * Đặt các loại MIME hợp lệ. Truyền mảng rỗng để dùng cấu hình mặc định.
     *
     * @param array<int, string> $types
     */
    public function allowedMimetypes(array $types = []): static
    {
        $this->allowed_mimetypes = ! empty($types)
            ? $types
            : (array) config('image-upload.allowed_mimetypes', []);

        return $this;
    }

    /**
     * Thiết lập các kích thước thumbnail. Không truyền sẽ lấy từ config.
     *
     * @param array<string, array<string, mixed>>|null $thumbnails
     */
    public function thumbnails(?array $thumbnails = null): static
    {
        $this->thumbnails = $thumbnails ?? (array) config('image-upload.thumbnails', []);

        return $this;
    }

    /**
     * Thực hiện lưu trữ file.
     *
     * @return string|array<int, string> URL hoặc danh sách URL nếu nhiều file.
     *
     * @throws Exception
     */
    public function store(): string|array
    {
        if (empty($this->file)) {
            throw new Exception('Không có tệp nào để tải lên.');
        }

        $urls = [];
        $manager = new ImageManager(new Driver());

        try {
            foreach ($this->file as $file) {
                $urls[] = $this->storeOne($manager, $file);
            }
        } catch (Throwable $exception) {
            throw new Exception('Không thể tải lên ảnh: ' . $exception->getMessage(), 0, $exception);
        }

        return count($urls) > 1 ? $urls : $urls[0];
    }

    /**
     * Xử lý và lưu một file.
     */
    protected function storeOne(ImageManager $manager, UploadedFile $file): string
    {
        $this->validateFileExtension($file);

        $originalExtension = strtolower($file->getClientOriginalExtension());

        // File video/audio: chỉ lưu, không xử lý ảnh
        if (in_array($originalExtension, self::MEDIA_EXTENSIONS, true)) {
            $filename = $this->generateFilename($file, $originalExtension);

            Storage::disk($this->disk)->putFileAs($this->folder, $file, $filename);

            return '/storage/' . ltrim($this->folder . '/' . $filename, '/');
        }

        $extension = $this->convert ?? $originalExtension;
        $encoded = $manager->read($file->getPathname())
            ->encodeByExtension($extension, progressive: true, quality: $this->quantity);

        $filename = $this->generateFilename($file, $extension);

        Storage::disk($this->disk)->put($this->folder . '/' . $filename, (string) $encoded);

        $this->generateThumbnails($manager, $file, $filename, $extension);

        return '/storage/' . ltrim($this->folder . '/' . $filename, '/');
    }

    /**
     * Tạo và lưu các thumbnail đã cấu hình.
     */
    protected function generateThumbnails(
        ImageManager $manager,
        UploadedFile $file,
        string $filename,
        string $extension
    ): void {
        foreach ($this->thumbnails as $thumbKey => $thumbOptions) {
            $thumbImage = $manager->read($file->getPathname());

            if (isset($thumbOptions['resize'])) {
                $width = $thumbOptions['resize']['width'] ?? null;
                $height = $thumbOptions['resize']['height'] ?? null;
                $upsize = $thumbOptions['resize']['upsize'] ?? false;

                $upsize
                    ? $thumbImage->scaleDown($width, $height)
                    : $thumbImage->scale($width, $height);
            }

            $url = $this->generateThumbnailFilename($filename, (string) $thumbKey, $extension);

            Storage::disk($this->disk)->put(
                $url,
                (string) $thumbImage->encodeByExtension($extension, progressive: true, quality: $this->quantity),
            );
        }
    }

    /**
     * Kiểm tra định dạng tệp dựa trên phần mở rộng.
     *
     * @throws Exception
     */
    protected function validateFileExtension(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = array_map('strtolower', $this->allowed_mimetypes);

        if (! in_array($extension, $allowed, true)) {
            throw new Exception(sprintf(
                'Loại tệp không hợp lệ: %s. Các loại tệp được phép: %s',
                $extension,
                implode(', ', $this->allowed_mimetypes),
            ));
        }
    }

    /**
     * Tạo tên tệp duy nhất.
     */
    protected function generateFilename(UploadedFile $file, string $extension): string
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
     */
    protected function generateThumbnailFilename(string $filename, string $thumbKey, string $extension): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);

        if ($this->filename_encoding) {
            $name = Str::slug($name, '-');
        }

        $buildUrl = fn (int|string $suffix = ''): string => $this->filename_encoding
            ? $this->folder . '/' . $thumbKey . '/' . $name . ($suffix !== '' ? '-' . $suffix : '') . '.' . $extension
            : $this->folder . '/' . $name . '-' . $thumbKey . ($suffix !== '' ? '-' . $suffix : '') . '.' . $extension;

        $url = $buildUrl();

        if ($this->overwrite) {
            return $url;
        }

        $count = 1;
        while (Storage::disk($this->disk)->exists($url)) {
            $url = $buildUrl($count++);
        }

        return $url;
    }
}

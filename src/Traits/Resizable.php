<?php

namespace Treconyl\ImagesUpload\Traits;

use Illuminate\Support\Str;

trait Resizable
{
    /**
     * Trả về URL hình thu nhỏ cụ thể cho mô hình.
     *
     * @param string $type Loại hình thu nhỏ (ví dụ: 'lg', 'md', 'sm').
     * @param string $attribute Tên thuộc tính chứa tên tệp hình ảnh.
     * @return string URL hình thu nhỏ hoặc chuỗi trống nếu không tìm thấy thuộc tính.
     */
    public function thumbnail($type, $attribute = 'image')
    {
        // Kiểm tra sự tồn tại của trường hình ảnh
        if (!isset($this->attributes[$attribute])) {
            return '';
        }

        // Lấy tên tệp hình ảnh từ thuộc tính
        $image = $this->attributes[$attribute];

        return $this->getThumbnail($image, $type);
    }

    /**
     * Tạo URL hình thu nhỏ dựa trên tên tệp gốc và loại hình thu nhỏ.
     *
     * @param string $image Tên tệp hình ảnh gốc.
     * @param string $type Loại hình thu nhỏ (ví dụ: 'lg', 'md', 'sm').
     * @return string URL hình thu nhỏ.
     */
    public function getThumbnail($image, $type)
    {
        // Phân tích tên tệp và phần mở rộng
        $ext = pathinfo($image, PATHINFO_EXTENSION);
    
        // Xóa phần mở rộng khỏi tên tệp gốc
        $name = Str::replaceLast('.' . $ext, '', $image);
    
        // Tạo đường dẫn thư mục chứa hình thu nhỏ
        $directory = dirname($name);
    
        // Lấy tên tệp mà không có thư mục
        $basename = basename($name);
    
        // Hợp nhất thư mục, loại hình thu nhỏ, và tên tệp gốc
        return $directory . '/' . $type . '/' . $basename . '.' . $ext;
    }
}

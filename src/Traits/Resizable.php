<?php

namespace Treconyl\ImageUpload\Traits;

use Illuminate\Support\Str;

trait Resizable
{
    /**
     * Phương pháp trả về hình thu nhỏ cụ thể cho mô hình.
     *
     * @param string $type
     * @param string $attribute
     *
     * @return string
     */
    public function thumbnail($type, $attribute = 'image')
    {
        // Trả về chuỗi trống nếu không tìm thấy trường
        if (!isset($this->attributes[$attribute])) {
            return '';
        }

        // Lấy hình ảnh từ trường bài viết
        $image = $this->attributes[$attribute];

        return $this->getThumbnail($image, $type);
    }

    /**
     * Tạo URL hình thu nhỏ.
     *
     * @param $image
     * @param $type
     *
     * @return string
     */
    public function getThumbnail($image, $type)
    {
        // Cần có loại tiện ích mở rộng ( .jpeg , .png ...)
        $ext = pathinfo($image, PATHINFO_EXTENSION);

        // Xóa tiện ích mở rộng khỏi tên tệp để chúng tôi có thể thêm loại hình thu nhỏ
        $name = Str::replaceLast('.'.$ext, '', $image);

        // Hợp nhất tên gốc + loại + phần mở rộng
        return $name.'-'.$type.'.'.$ext;
    }
}

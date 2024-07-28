# Uploads Image
## Install
```
composer require treconyl/images-upload

php artisan vendor:publish --provider="Treconyl\ImagesUpload\ImagesUploadServiceProvider"
```

## User manual Helper ImageUpload
```
use Treconyl\ImagesUpload\ImageUpload;

$file = ImageUpload::file($request, 'image')
                    // ->folder()
                    // ->allowedMimetypes()
                    // ->convert()
                    // ->thumbnails()
                    ->store();


- Phương thức file($request, $input, $quantity = 100, $filename_encoding = true, $overwrite = false)
$request Illuminate\Http\Request
$input tên name của input tải lên
$quantity chất lượng hình ảnh muốn thay đổi
$filename_encoding định nghĩa lại tên ảnh ví dụ Ảnh chụp màn hình.jpg => anh-chup-man-hinh.jpg
$overwrite nếu trùng tên có trong hệ thống sẽ tự động thay thế (nên tắt), nếu đang tắt khi tải lên trùng tên sẽ được thêm số vào cuối tệp, ví dụ anh-1.jpg

- Phương thức folder($name = 'default', $timestamp = true, $disk = 'public')
$name tên thư mục tải lên chứa hình ảnh
$timestamp có tự động thêm 1 thư mục con với định dạng July2024 với tháng năm hiện tại hay không? (khuyên dùng dễ kiểm soát ảnh hơn)
$disk tên disks trong config filesystems mà bạn đã cấu hình để tải lên

- Phương thức allowedMimetypes($mime = ['jpeg', 'jpg', 'png', 'gif', 'webp'])
tham số truyền vào là 1 mảng chứa extension muốn kiểm tra khi tải lên, nếu không truyền sẽ lấy từ file config mặc định để kiểm tra extension trước khi tải lên

- Phương thức convert($extension = 'webp')
Chuyển đổi và nén sang 1 định dạng mới (khuyên dùng webp)

- Phương thức thumbnails(['lg' => ['resize' => ['width' => 600]], 'md' => ['resize' => ['width' => 300]]])
Chứa mảng bao gồm các ảnh nhỏ hơn từ ảnh gốc để đáp ứng cho các loại thiết bị iphone, ipad, macbook...
lg, md là ký hiệu cho ảnh thu nhỏ này cũng là thư mục chứa chúng
'resize' => ['width' => 600] có ý nghĩa là sử dụng kỹ thuật nén resize ảnh, có nhiều loại kỹ thuật nén khách nhau tuỳ nhu cầu sử dụng như "resize", "crop", "scale", "scaleDown", "cover", "contain". Xem thêm ở file config/image-upload.php

- Phương thức store()
Hàm end kết thúc xử lý, có thể thay thế bằng log() để hiển thị thông tin kiểm tra lỗi
```

## User manual Traits Resizable
```
- Thêm vào modal bạn muốn sử dụng để lấy các hình ảnh thumbails

use Treconyl\ImagesUpload\Traits\Resizable;
class User extends Model
{
    use HasFactory, Resizable;
}
Sau khi thêm giờ bạn có thể sử dụng được thumbnail và getThumbnail

- Ví dụ khi dùng trong blade.php

Display a single image
@foreach($posts as $post)
    <img src="{{$post->thumbnail('sm')}}" alt="image post"/>
@endforeach

Display multiple images
@foreach($posts as $post)
    $images = json_decode($post->images);
    @foreach($images as $image)
        <img src="{{ $post->getThumbnail($image, 'sm') }}" />
    @endforeach
@endforeach
```
# Images Upload
## Bắt đầu
```
composer require treconyl/images-upload

php artisan vendor:publish --provider="Treconyl\ImageUpload\ImageUploadServiceProvider" --tag=config
```


## Sử dụng Traits trong model để bung hình thumbnails
### Model
```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Treconyl\ImageUpload\Traits\Resizable;

class Fight extends Model
{
    use Resizable;
}
```

### Get thumbnails
```
<?php
    $images = json_decode($article->multiple_image);
?>

@foreach($images as $image)
    <img src="{{ ImageUpload::image($article->getThumbnail($image, 'md')) }}" alt="{{ $article->name }}">
@endforeach
```
## Store Image Upload
### Chỉ lấy đường dẫn của ảnh
```
ImageUpload::file($request, 'multiple_image')->extension('webp')->getPath('product');
```
### Tải lên ảnh và trả về đường dẫn
#### Watermask
```
$folder = 'product';
ImageUpload::file($request, 'image')
            ->watermask('watermask/default.png', 'bottom-left', 0, 0)
            ->extension('webp')
            ->store($folder);
```
#### Resize
```
ImageUpload::file($request, 'image')
            ->resize(800, null, 100, true)
            ->extension('webp')
            ->store(); // store folder name is default
```
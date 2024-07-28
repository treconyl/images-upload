```
use Treconyl\ImagesUpload\ImageUpload;

$file = ImageUpload::file($request, 'image')
                    // ->folder('product')
                    // ->allowedMimetypes()
                    // ->convert('webp')
                    // ->thumbnails([
                    //     'lg' => ['resize' => ['width' => 600]],
                    //     'md' => ['resize' => ['width' => 300]]
                    // ])
                    ->store();
```
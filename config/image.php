<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Extentions hỗ trợ tải lên hình ảnh
    |--------------------------------------------------------------------------
    |
    | Supported: "gd", "imagick"
    |
    */
    'driver' => 'gd',

    /*
    |--------------------------------------------------------------------------
    | Folder đường dẫn folder lưu ảnh
    |--------------------------------------------------------------------------
    |
    | Ex: April2020
    |
    */
    'folder' => date('F') . date('Y'),

    /*
    |--------------------------------------------------------------------------
    | Hỗ trợ định dạng file cho phép tải lên
    |--------------------------------------------------------------------------
    |
    | Example: "jpeg", "jpg", "png", "gif", "webp", ...
    |
    */
    'allowed_mimetypes' => [
        'jpeg', 'jpg', 'png', 'gif', 'webp'
    ],

    /*
    |--------------------------------------------------------------------------
    | Hỗ trợ chèn ảnh watermask
    |--------------------------------------------------------------------------
    |
    */
    'watermask' => [
        'public_path'   => public_path('watermask/default.png'),
        'position'      => 'bottom-left',
        'x'             => 0,
        'y'             => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cấu hình ảnh mặc định khi tải lên và chất lượng ảnh
    |--------------------------------------------------------------------------
    |  width: null không làm gì cả :)
    |  height: null nếu đặt chiều cao tự động theo chiều rộng
    |  quality: tất cả các ảnh tải lên sẽ được gán chất lượng
    |  upsize: ngăn chặn tăng kích thước có thể xảy ra 
    */
    "resize" => [
        "width"    => 1600, // null chiều rộng lớn nhất
        "height"   => null, // null chiều cao tự động
        "quantity" => 100,  // tất cả các ảnh tải lên sẽ được gán chất lượng
        "upsize"   => true  // ngăn chặn tăng kích thước có thể xảy ra
    ],

    /*
    |--------------------------------------------------------------------------
    | Thêm các ảnh nhỏ hơn, sắp xếp theo thứ tự cao đến thấp lg-md-sm-xs để không bị vỡ ảnh
    |--------------------------------------------------------------------------
    |  Supported: "resize", "fit", "crop"
    */
    "thumbnails" => [
        "lg" => [
            "resize" => [
                "width"   => 1600,
                "height"  => null,
                "upsize"  => true
            ]
        ],
        "md" => [
            "resize" => [
                "width"   => 800,
                "height"  => null,
                "upsize"  => true
            ]
        ]
        
    ]
];

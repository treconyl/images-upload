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
        "width"   => 500,
        "height"  => null, // null chiều cao tự động
        "quality" => 100,  // tất cả các ảnh tải lên sẽ được gán chất lượng
        "upsize"  => true  // ngăn chặn tăng kích thước có thể xảy ra
    ],

    /*
    |--------------------------------------------------------------------------
    | Thêm các ảnh nhỏ hơn
    |--------------------------------------------------------------------------
    |  Supported: "resize", "fit", "crop"
    */
    "thumbnails" => [
        "md" => [
            "resize" => [
                "width"   => 800,
                "height"  => null
            ]
        ],
        "lg" => [
            "resize" => [
                "width"   => 1600,
                "height"  => null,
                "upsize"  => true
            ]
        ]
    ]
];
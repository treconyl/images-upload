<?php

return [
    'allowed_mimetypes' => [
        'jpeg', 'jpg', 'png', 'gif', 'webp'
    ],

    // Thông số mặc định áp dụng toàn cầu cho các ảnh khi tải lên
    'resize' => [
        'width' => 1600,
        'height' => null,
        'quantity' => 100,
        'upsize' => true
    ],

    /*
    |--------------------------------------------------------------------------
    | Thêm các ảnh nhỏ hơn để phù hợp từng thiết bị, recomment (resize)
    |--------------------------------------------------------------------------
    | Supported: "resize", "crop", "scale", "scaleDown", "cover", "contain"
    */
    'thumbnails' => [
        'lg' => [
            'resize' => [
                'width' => 1600,
                'height' => null
            ]
        ],
        'md' => [
            'resize' => [
                'width' => 800,
                'height' => null
            ]
        ],
        'scale' => [
            'scale' => [
                'width' => null,
                'height' => 300
            ]
        ],
        'crop' => [
            'crop' => [
                'width' => null,
                'height' => 300,
                'offset_x' => 0,
                'offset_y' => 30,
                'position' => 'bottom-right',
            ]
        ],
        'scaleDown' => [
            'scaleDown' => [
                'width' => 200,
                'height' => 300,
                'position' => 'center'
            ]
        ],
        'cover' => [
            'cover' => [
                'width' => 200,
                'height' => 300,
                'position' => 'center'
            ]
        ],
        'contain' => [
            'contain' => [
                'width' => 200,
                'height' => 300,
                'position' => 'center'
            ]
        ],
    ],
];
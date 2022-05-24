<?php

return [
    'classes' => [
        'media' => \Zencoreitservices\MediaManager\Models\Media::class,
        'image-processor' => \Zencoreitservices\MediaManager\Processors\ImageProcessor::class,
    ],

    'disk' => 'public',

    'image-driver' => 'imagick',

    'background-color' => '#FFFFFF',

    'image-types' => [
        /*
        'product' => [
            'miniature' => [
                'width' => 250,
                'height' => 140,
                'fit' => 'cover',
            ],
            'default' => [
                'width' => 690,
                'height' => 388,
                'fit' => 'cover',
            ],
            'full_hd' => [
                'width' => 1920,
                'height' => 1080,
                'fit' => 'cover',
            ],
        ]
        */
    ],
];
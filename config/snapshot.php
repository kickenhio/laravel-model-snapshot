<?php

return [
    'warnings'  => false,
    'manifests' => [
        'example' => [
            'file_path'  => resource_path('example.json'),
            'connection' => 'mysql',
            'encryption' => [
                'noise' => env('SNAPSHOT_API_EXAMPLE_NOISE_LEN', 12),
                'key'   => env('SNAPSHOT_API_EXAMPLE_KEY', 'Pqan5NghSZ4vZBuP'),
                'iv'    => env('SNAPSHOT_API_EXAMPLE_IV', 'JabXMkEh92EVxUnq'),
            ]
        ],
    ],
];
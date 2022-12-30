<?php

return [
    'warnings'  => false,
    'api_prefix' => 'snapshot/api',
    'manifests' => [
        'example' => [
            'file_path'  => resource_path('example.json'),
            'connection' => 'mysql',
            'encryption' => [
                'frame' => env('SNAPSHOT_API_EXAMPLE_FRAME', 12),
                'key'   => env('SNAPSHOT_API_EXAMPLE_KEY', 'Pqan5NghSZ4vZBuP'),
                'iv'    => env('SNAPSHOT_API_EXAMPLE_IV', 'JabXMkEh92EVxUnq'),
            ]
        ],
    ],
];
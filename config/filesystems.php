<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'throw' => true,
        ],
    ],
    'links' => [public_path('storage') => storage_path('app/public')],
];

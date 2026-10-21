<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL', 'http://localhost').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Candidate CVs. Private: no public URL exists for this disk.
               // Candidate CVs. Private: no public URL exists for this disk.
        // Local by default; set CV_DISK_DRIVER=s3 in production (Cloudflare R2 / S3).
        'cvs' => env('CV_DISK_DRIVER', 'local') === 's3'
            ? [
                'driver' => 's3',
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION', 'auto'),
                'bucket' => env('AWS_BUCKET'),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
                'visibility' => 'private',
                'throw' => true,
                'report' => false,
            ]
            : [
                'driver' => 'local',
                'root' => storage_path('app/private/cvs'),
                'visibility' => 'private',
                'throw' => true,
                'report' => false,
            ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

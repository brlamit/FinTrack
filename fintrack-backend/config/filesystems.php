<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'supabase' => [
        'driver'         => 's3',
        'key'            => env('SUPABASE_KEY'),           // service role key
        'secret'         => env('SUPABASE_SECRET'),         // service role secret
        'endpoint'       => env('SUPABASE_ENDPOINT'),       // S3-compatible endpoint
        'region'         => 'auto',                         // required for R2/Supabase
        'bucket'         => env('SUPABASE_BUCKET', 'avatars'),
        'url'            => env('SUPABASE_PUBLIC_URL'),     // direct public URL
        'use_path_style_endpoint' => false,
        'throw'          => false,
        'options'        => [
            'OverrideContentType' => 'auto',
        ],
        // Critical for Cloudflare R2 / Supabase Storage
        'bucket_endpoint' => false,
    ],

           // Supabase (S3-compatible) disk configurations
        'supabase_public' => [
            'driver' => 's3',
            'key' => env('SUPABASE_KEY'),
            'secret' => env('SUPABASE_SECRET'),
            'region' => env('SUPABASE_REGION', 'us-east-1'),
            'bucket' => env('SUPABASE_PUBLIC_BUCKET'),
            'url' => env('SUPABASE_URL'),
            'endpoint' => env('SUPABASE_ENDPOINT'),
            'use_path_style_endpoint' => env('SUPABASE_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => true,
            'report' => false,
        ],

        'supabase_private' => [
            'driver' => 's3',
            'key' => env('SUPABASE_KEY'),
            'secret' => env('SUPABASE_SECRET'),
            'region' => env('SUPABASE_REGION', 'us-east-1'),
            'bucket' => env('SUPABASE_PRIVATE_BUCKET'),
            'url' => env('SUPABASE_URL'),
            'endpoint' => env('SUPABASE_ENDPOINT'),
            'use_path_style_endpoint' => env('SUPABASE_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => true,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

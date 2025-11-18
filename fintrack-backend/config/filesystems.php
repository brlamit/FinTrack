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

        // Supabase (S3-compatible) disk. Fill these ENV values in your .env file if you want
        // to store uploads in a Supabase bucket.
        // Supabase S3-compatible disks. Configure multiple disks pointing to different buckets
        // so the application can store avatars, documents, etc. in separate Supabase buckets.
        // 'supabase' => [
        //     'driver' => 's3',
        //     'key' => env('SUPABASE_SERVICE_KEY'),
        //     'secret' => env('SUPABASE_SERVICE_SECRET'),
        //     'region' => env('SUPABASE_REGION', 'us-east-1'),
        //     'bucket' => env('SUPABASE_BUCKET_DEFAULT', 'avatars'),
        //     'endpoint' => env('SUPABASE_ENDPOINT'),
        //     'use_path_style_endpoint' => env('SUPABASE_USE_PATH_STYLE_ENDPOINT', true),
        //     'visibility' => 'public',
        // ],

        // 'supabase_avatars' => [
        //     'driver' => 's3',
        //     'key' => env('SUPABASE_SERVICE_KEY'),
        //     'secret' => env('SUPABASE_SERVICE_SECRET'),
        //     'region' => env('SUPABASE_REGION', 'us-east-1'),
        //     'bucket' => env('SUPABASE_BUCKET_AVATARS', env('SUPABASE_BUCKET_DEFAULT', 'avatars')),
        //     'endpoint' => env('SUPABASE_ENDPOINT'),
        //     'use_path_style_endpoint' => env('SUPABASE_USE_PATH_STYLE_ENDPOINT', true),
        //     'visibility' => 'public',
        // ],

        'supabase_documents' => [
            'driver' => 's3',
            'key' => env('SUPABASE_SERVICE_KEY'),
            'secret' => env('SUPABASE_SERVICE_SECRET'),
            'region' => env('SUPABASE_REGION', 'us-east-1'),
            'bucket' => env('SUPABASE_BUCKET_DOCUMENTS', 'documents'),
            'endpoint' => env('SUPABASE_ENDPOINT'),
            'use_path_style_endpoint' => env('SUPABASE_USE_PATH_STYLE_ENDPOINT', true),
            'visibility' => 'public',
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

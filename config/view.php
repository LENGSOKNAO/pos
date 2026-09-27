<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. On read-only (serverless) filesystems
    | compiled views fall back to the system temp directory.
    |
    */

    'compiled' => env('VIEW_COMPILED_PATH', is_writable(storage_path('framework/views')) ? storage_path('framework/views') : sys_get_temp_dir()),

];

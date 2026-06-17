<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Railway/Nixpacks runs `php artisan view:cache` during build. This config
    | guarantees Laravel can always resolve the Blade view directory inside
    | the deployed container.
    |
    */

    'paths' => [
        base_path('resources/views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views')) ?: storage_path('framework/views')
    ),

];

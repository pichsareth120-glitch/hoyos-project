<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Configured to point to the unified frontend views directory in the
    | restructured monolith layout (hoyos-project/frontend/views).
    |
    */

    'paths' => [
        realpath(base_path('../frontend/views')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | Compiled Blade templates are stored within the backend storage directory.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];

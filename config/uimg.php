<?php

return [

    /*
    |--------------------------------------------------------------------------
    | µIMG configuration
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL'),

    'home_text' => env('HOME_TEXT', 'A micro self-hosted image dump with CLI upload.'),

    # since a byte is expressed as two hex characters; the string will be double this length
    'api_key_length' => env('API_KEY_LENGTH', 16),

    'cache_header' => [
        'home'  => 'public, max-age=' . 60*30,       // 30 minutes
        'image' => 'public, max-age=' . 60*60*24*90  // 90 days
    ]

];

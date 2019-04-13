<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

# Home
$router->get('/', function () {
    return response(view('home'))->header('Cache-Control', 'public, max-age=' . 60*30);
});

# Upload
$router->post('/upload', [ 'uses' => 'UploadController@create' ]);

# Show
$router->get('/{file:[0-9a-z]+}.{ext:[a-z]+}', [ 'uses' => 'DisplayController@show' ]);

# Resize
$router->get('/{w:[0-9]+}x{h:[0-9]+}/{file:[0-9a-z]+}.{ext:[a-z]+}', [ 'uses' => 'DisplayController@resize' ]);

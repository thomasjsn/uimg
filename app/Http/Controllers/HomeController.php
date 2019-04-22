<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }


    public function show()
    {
        $images = Redis::hget('stats', 'images') ?: 0;
        $derivatives = Redis::hget('stats', 'derivatives') ?: 0;

        return response(view('home', compact('images', 'derivatives')))
            ->header('Cache-Control', 60*30);
    }

}

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
        $images = Redis::dbsize();

        $text = config('uimg.home_text');

        return response(view('home', compact('images', 'text')))
            ->header('Cache-Control', 'public, max-age=' . 60*30);
    }

}

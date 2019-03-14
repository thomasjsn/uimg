<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

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
        $images = DB::table('images')->count();

        $size = DB::table('images')->sum('size');
        $size = $this->formatBytes($size);

        $last = DB::table('images')->orderBy('created', 'desc')->first();

        if (! is_null($last)) {
            $last = Carbon::createFromTimeString($last->created)->diffForHumans();
        } else {
            $last = '∞';
        }

        return view('home', compact('images', 'size', 'last'));
    }

}

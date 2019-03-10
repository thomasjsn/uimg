<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cache;
use DB;
use Storage;

class DisplayController extends Controller
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


    public function show($file, $ext)
    {
        $filename = $file . '.' . $ext;

        if (! Storage::disk('minio')->exists($filename . '/' . $filename)) abort(404);

        $image = Storage::disk('minio')->get($filename . '/' . $filename);
            
        $type = DB::table('images')->where('id', $file)->value('mime_type');

        if (is_null($type)) abort(404);

        DB::table('images')->where('id', $file)->increment('accessed');

        return response($image, 200)->header('Content-Type', $type);
    }


    public function resize($w, $h, $file, $ext)
    {
        if ($w > 2000 || $h > 2000) {
            return response()->json(['status'=>'error','reason'=>'Width || height > 2000'], 400);
        }

        $filename = $file . '.' . $ext;
        $path = $w . 'x' . $h . '/' . $filename;
        
        if (! Storage::disk('minio')->exists($filename . '/' . $filename)) abort(404);

        $image = $this->scaleImage($w, $h, $filename, $path);
            
        $type = DB::table('images')->where('id', $file)->value('mime_type');

        if (is_null($type)) abort(404);

        DB::table('images')->where('id', $file)->increment('accessed');

        return response($image, 200)->header('Content-Type', $type);
    }


    private function scaleImage($w, $h, $filename, $path)
    {
        if (Storage::disk('minio')->exists($filename . '/' . $path)) {
            return Storage::disk('minio')->get($filename . '/' . $path);
        }

        $org = Storage::disk('minio')->get($filename . '/' . $filename);
        Storage::disk()->put($filename, $org);

        $imagick = new \Imagick(realpath('../storage/app/' . $filename));
        $imagick->scaleImage($w, $h, true);
        $imagick->writeImage('../storage/app/' . $w . $h . $filename);

        $image = $imagick->getImagesBlob();
        $imagick->destroy();

        Storage::disk('minio')->put($filename . '/' . $path, $image);

        return $image;
    }

}

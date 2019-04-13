<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
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

        if (! Storage::cloud()->exists($filename . '/' . $filename)) abort(404);
        $image = Storage::cloud()->get($filename . '/' . $filename);

        $imageData = Redis::get($file);
        if (is_null($imageData)) abort(404);

        $type = json_decode($imageData)->mime_type;

        Redis::expire($file, 3600*24*365);

        return response($image, 200)
            ->header('Content-Type', $type)
            ->header('Cache-Control', 'public, max-age=' . 60*60*24*90);
    }


    public function resize($w, $h, $file, $ext)
    {
        if ($w > 2000 || $h > 2000 || $w < 10 || $h < 10) {
            return response()->json([
                'status' => 'error',
                'error' => 400,
                'message' => 'Width and height must be between 10 and 2000'
            ], 400);
        }

        $filename = $file . '.' . $ext;
        $path = $w . 'x' . $h . '/' . $filename;
        
        if (! Storage::cloud()->exists($filename . '/' . $filename)) abort(404);
        list($image, $proc) = $this->scaleImage($w, $h, $filename, $path);
            
        $imageData = Redis::get($file);
        if (is_null($imageData)) abort(404);

        $type = json_decode($imageData)->mime_type;

        Redis::expire($file, 3600*24*365);

        return response($image, 200)
            ->header('Content-Type', $type)
            ->header('Cache-Control', 'public, max-age=' . 60*60*24*90)
            ->header('X-Image-Derivative', $proc);
    }


    private function scaleImage($w, $h, $filename, $path)
    {
        if (Storage::cloud()->exists($filename . '/' . $path)) {
            \Log::info('Found existing scaled image', ['img' => $filename, 'dim' => $w . 'x' . $h]);
            $image = Storage::cloud()->get($filename . '/' . $path);

            return [$image, 'found'];
        }

        $org = Storage::cloud()->get($filename . '/' . $filename);
        Storage::disk()->put($filename, $org);

        $imagick = new \Imagick(realpath('../storage/app/' . $filename));
        $imagick->scaleImage($w, $h, true);

        $image = $imagick->getImagesBlob();
        $imagick->destroy();

        Storage::cloud()->put($filename . '/' . $path, $image);
        Storage::disk()->delete($filename);

        \Log::info('Image scaled', ['img' => $filename, 'dim' => $w . 'x' . $h]);

        return [$image, 'created'];
    }

}

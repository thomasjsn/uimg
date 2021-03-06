<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Storage;

class UploadController extends Controller
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


    public function create(Request $request)
    {
        $file = $request->file('file');
        $key = $request->input('key');

        if (! $request->file('file')->isValid()) {
            return response()->json([
                'status' => 'error',
                'error' => 400,
                'message' => 'Uploaded file is missing or not valid'
            ], 400);
        }

        $fileContent = file_get_contents($file);
        $size = $file->getClientSize();
        $hash = $this->getNewHash();

        $keyData = Redis::get('apikey:' . $key);
        if (is_null($keyData)) {
            return response()->json([
                'status' => 'error',
                'error' => 403,
                'message' => 'Incorrect or missing key'
            ], 403);
        }

        $key_ttl = Redis::ttl('apikey:' . $key);
        if ($key_ttl > 0) {
            $key_ttl = Carbon::now()->addSeconds($key_ttl)->diffInDays();
        }

        Storage::disk()->put($hash, $fileContent);

        try {
            # http://www.php.net/manual/en/function.exif-imagetype.php
            $type = exif_imagetype($file);
        } catch (\ErrorException $e) {
            return response()->json([
                'status' => 'error',
                'error' => 400,
                'message' => 'Unable to determine image type'
            ], 400);
        }

        switch($type)
        {
            case 1: $ext = 'gif'; break;
            case 3: $ext = 'png'; break;
            case 6: $ext = 'bmp'; break;
            case 17: $ext = 'ico'; break;
            case 18: $ext = 'webp'; break;
            case 2:
                $img = new \Imagick(realpath('../storage/app/' . $hash));
				$this->autoRotateImage($img);
                $profiles = $img->getImageProfiles("icc", true);
                $img->stripImage();
				if(!empty($profiles)) {
				   $img->profileImage("icc", $profiles['icc']);
				}
                $ext = 'jpg';
                $fileContent = $img->getImagesBlob();
                $img->destroy();
            break;
            default:
                return response()->json([
                    'status' => 'error',
                    'error' => 400,
                    'message' => 'Not a valid image'
                ], 400);
        }

        $filename = $hash . '.' . $ext;
        $checksum = sha1_file($file);
        $url = config('app.url') . '/' . $filename;

        $existingImageHash = Redis::get('checksum:' . $checksum);
        $existingImageData = Redis::get('image:' . $existingImageHash);

        if (! is_null($existingImageHash) && ! is_null($existingImageData)) {
            \Log::info('Image already uploaded', ['img' => $filename]);
            Storage::disk()->delete($hash);

            return response()->json([
                'status'=>'ok',
                'message' => 'Image already uploaded',
                'key_ttl_d' => $key_ttl,
                'url' => config('app.url') . '/' . json_decode($existingImageData)->filename
            ], 200);
        }

        Storage::cloud()->put($filename . '/' . $filename, $fileContent);

        Redis::set('image:' . $hash, json_encode([
            'filename' => $filename,
            'mime_type' => mime_content_type(realpath('../storage/app/' . $hash)),
            'checksum' => $checksum
        ]));
        Redis::expire('image:' . $hash, 3600*24*7);

        Redis::set('checksum:' . $checksum, $hash);
        Redis::expire('checksum:' . $checksum, 3600*24*7);

        Storage::disk()->delete($hash);

		$response = [
            'status' => 'ok',
            'message' => 'Image successfully uploaded',
            'size_mib' => round($size / 1024 / 1024, 3),
            'key_ttl_d' => $key_ttl,
            'url' => $url,
        ];

        \Log::info('Image successfully uploaded', ['img' => $filename]);

        return response()->json($response, 201);
    }


	function autoRotateImage($image) {
		$orientation = $image->getImageOrientation();

		switch($orientation) {
			case \Imagick::ORIENTATION_BOTTOMRIGHT:
				$image->rotateimage("#000", 180); // rotate 180 degrees
			break;

			case \Imagick::ORIENTATION_RIGHTTOP:
				$image->rotateimage("#000", 90); // rotate 90 degrees CW
			break;

			case \Imagick::ORIENTATION_LEFTBOTTOM:
				$image->rotateimage("#000", -90); // rotate 90 degrees CCW
			break;
		}
	} 

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
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
        $fileContent = file_get_contents($file);
        $size = $file->getClientSize();
        $hash = $this->getNewHash();

        Storage::disk()->put($hash, $fileContent);

        $type = exif_imagetype($file); //http://www.php.net/manual/en/function.exif-imagetype.php
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
        $url = env('APP_URL') . '/' . $filename;

        if (DB::table('images')->where('checksum', $checksum)->count() > 0) {
            \Log::info('Image already uploaded', ['img' => $filename]);
            Storage::disk()->delete($hash);

            $existingImage = DB::table('images')->where('checksum', $checksum)->first();

            return response()->json([
                'status'=>'ok',
                'operation' => 'retrieve',
                'message' => 'Image already uploaded',
                'image_id' => $existingImage->id,
                'url' => env('APP_URL') . '/' . $existingImage->filename
            ], 200);
        }

        Storage::cloud()->put($filename . '/' . $filename, $fileContent);

        $token = bin2hex(random_bytes(32));

        DB::table('images')->insert([
            'id' => $hash,
            'filename' => $filename,
            'mime_type' => mime_content_type(realpath('../storage/app/' . $hash)),
            'checksum' => $checksum,
            'size' => $size,
            'token' => $token,
            'timestamp' => Carbon::now()
        ]);

        Storage::disk()->delete($hash);

		$response = [
            'status' => 'ok',
            'operation' => 'create',
            'message' => 'Image successfully uploaded',
            'image_id' => $hash,
            'token' => $token,
			'url' => $url
        ];

        \Log::info('Image successfully uploaded', ['img' => $filename]);

        return response()->json($response, 201);
    }


	private function getNewHash($length = 6)
    {
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';

		while(1)
		{
			$hash = $this->generateString($permitted_chars, $length);
			if (DB::table('images')->where('id', $hash)->count() == 0) return $hash;
			$length++;
		}
	}


    private function generateString($input, $strength)
    {
		$input_length = mb_strlen($input, '8bit');
        $random_string = '';

		for($i = 0; $i < $strength; $i++) {
			$random_string .= $input[mt_rand(0, $input_length - 1)];
		}

		return $random_string;
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

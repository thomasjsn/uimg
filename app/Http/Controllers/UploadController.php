<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $checksum = sha1_file($file);
        $size = $file->getClientSize();
        $hash = $this->getNewHash();

        Storage::disk()->put($hash, file_get_contents($file));

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
            break;
            default:
                return response()->json(['status'=>'error','reason'=>'Not a valid image']);
        }

        $filename = $hash . '.' . $ext;
        $url = env('APP_URL') . '/' . $filename;

        if (DB::table('images')->where('checksum', $checksum)->count() > 0) {
            return response()->json([
                'status'=>'ok',
                'message'=>'Image already uploaded',
                'url' => env('APP_URL') . '/' . DB::table('images')->where('checksum', $checksum)->value('filename')
            ]);
        }

        Storage::disk('minio')->put($filename . '/' . $filename, $img->getImagesBlob());
        $img->destroy();

        DB::table('images')->insert([
            'id' => $hash,
            'filename' => $filename,
            'mime_type' => mime_content_type(realpath('../storage/app/' . $hash)),
            'checksum' => $checksum,
            'size' => $size,
            'timestamp' => \Carbon\Carbon::now()
        ]);

        Storage::disk()->delete($hash);

		$response = [
            'status' => 'ok',
            'message' => 'Image successfully uploaded',
			'url' => $url
		];

        return response()->json($response);
    }


	private function getNewHash($length=6)
	{
		while(1)
		{
			$hash = $this->getRandomString($length);
			if (DB::table('images')->where('id', $hash)->count() == 0) return $hash;
			$length++;
		}
	}


	private function getRandomString($length=32, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyz')
	{
		$str = '';
		$max = mb_strlen($keyspace, '8bit') - 1;
		for ($i = 0; $i < $length; ++$i) {
			$str .= $keyspace[rand(0, $max)];
		}
		return $str;
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

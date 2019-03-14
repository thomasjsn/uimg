<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Storage;

class DeleteController extends Controller
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


    public function destroy($file, $ext, Request $request)
    {
        $key = $request->input('key');
        $keyId = $this->getKeyId($key);

        $filename = $file . '.' . $ext;
        $image = DB::table('images')->where(['id' => $file, 'filename' => $filename])->first();
        if (is_null($image)) abort(404);

        if (is_null($keyId) || $keyId != $image->api_key_id) {
            return response()->json([
                'status' => 'error',
                'error' => 403,
                'message' => 'Incorrect or missing key'
            ], 403);
        }

        DB::table('images')->where(['id' => $file, 'filename' => $filename])->delete();
        Storage::cloud()->deleteDirectory($image->filename);

        \Log::info('Image deleted', ['img' => $image->filename]);

        return response()->json([
            'status'=>'ok',
            'operation' => 'destroy',
            'message' => 'Image was deleted',
            'image_id' => $image->id
        ], 200);
    }

}

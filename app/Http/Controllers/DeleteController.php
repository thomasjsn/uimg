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


    public function destroy($id, $token)
    {
        $image = DB::table('images')->where(['id' => $id, 'token' => $token])->first();

        if (is_null($image)) {
            return response()->json([
                'status' => 'error',
                'error' => 404,
                'message' => 'Image not found, or token incorrect'
            ], 404);
        }

        DB::table('images')->where(['id' => $id, 'token' => $token])->delete();
        Storage::disk('minio')->deleteDirectory($image->filename);

        \Log::info('Image deleted', ['img' => $image->filename]);

        return response()->json([
            'status'=>'ok',
            'operation' => 'destroy',
            'message' => 'Image was deleted',
            'image_id' => $image->id
        ], 200);
    }

}

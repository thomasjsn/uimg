<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Storage;

class ImagesCleanUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up images';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $neverAccessed = DB::table('images')->where('accessed', 0)->whereRaw('timestamp < NOW() - INTERVAL 1 WEEK')->get();

        foreach ($neverAccessed as $image) {
            $this->info($image->filename);
            Storage::disk('minio')->deleteDirectory($image->filename);
            DB::table('images')->where('id', $image->id)->delete();
        }
    }
}


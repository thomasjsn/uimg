<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Storage;

class ImageCleanUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:cleanup {--dry-run}';

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
        $unseen = DB::table('images')
            ->whereNull('last_viewed')
            ->whereRaw('created < NOW() - INTERVAL 90 DAY')
            ->get();
        foreach ($unseen as $image) {
            $this->info('Unseen: ' . $image->filename);
            $this->purgeImage($image);
        }

        $stale = DB::table('images')
            ->whereNotNull('last_viewed')
            ->whereRaw('last_viewed < NOW() - INTERVAL 1 YEAR')
            ->get();
        foreach ($stale as $image) {
            $this->info('Turned stale: ' . $image->filename);
            $this->purgeImage($image);
        }

        $images = DB::table('images')
            ->get();
        foreach ($images as $image) {
            if (! Storage::cloud()->exists($image->filename)) {
                $this->info('Image file missing: ' . $image->filename);
                $this->purgeImage($image);
            }
        }
    }


    private function purgeImage($image)
    {
        if ($this->option('dry-run')) {
            $this->comment('Dry-run, nothing is deleted');
            return;
        }

        Storage::cloud()->deleteDirectory($image->filename);
        DB::table('images')->where('id', $image->id)->delete();
    }
}


<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use DB;
use Storage;

class ImageCleanUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:cleanup {--dry-run : Don\'t do anything} {--derivatives : Purge old derivatives}';

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

        $keys = Redis::keys('*');
        foreach ($keys as $key) {
            $filename = json_decode(Redis::get($key))->filename;

            if (! Storage::cloud()->exists($filename)) {
                $this->info('Image file missing: ' . $filename);
                $this->purgeImage($key, $filename);
            }
        }

        $directories = Storage::cloud()->directories();
        foreach ($directories as $directory) {
            $hash = strstr($directory, '.', true);
            $image = Redis::get($hash);

            if (is_null($image)) {
                $this->info('DB key missing: ' . $filename);
                $this->purgeImage($hash, $directory);
            }
        }

        if ($this->option('derivatives')) {
            $this->clearStaleDerivatives();
        };
    }


    private function purgeImage($hash, $filename)
    {
        if ($this->option('dry-run')) {
            $this->comment('Dry-run, nothing is deleted');
            return;
        }

        Storage::cloud()->deleteDirectory($filename);
        Redis::del($hash);
    }


    private function clearStaleDerivatives()
    {
        $keys = Redis::keys('*');

        foreach ($keys as $key) {
            $filename = json_decode(Redis::get($key))->filename;
            $derivatives = Storage::cloud()->directories($filename);

            foreach ($derivatives as $derivative) {
                $lastMod = Storage::cloud()->lastModified($derivative . '/' . $filename);

                $dt = Carbon::createFromTimestamp($lastMod);
                $age = Carbon::now()->diffInDays($dt);

                $this->line(sprintf('%s : %s', $derivative, $dt->diffForHumans()));

                if ($age > 90) {
                    $this->info(sprintf('%s : Deleted, age: %d', $derivative, $age));
                    $lastMod = Storage::cloud()->deleteDirectory($derivative);
                }
            }
        }
    }
}


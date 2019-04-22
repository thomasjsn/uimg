<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Redis;
use Storage;

class ImageStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate some image statistics';

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
        $images = count($this->scanAllForMatch('image:*'));
        $derivatives = count(Storage::cloud()->allDirectories()) - $images;

        $stats = [
            [ 'Images', $images ],
            [ 'Derivatives', $derivatives ]
        ];

        $this->table(['Parameter', 'Value'], $stats);

        Redis::hset('stats', 'images', $images);
        Redis::hset('stats', 'derivatives', $derivatives);
    }

}


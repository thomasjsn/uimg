<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
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
    protected $description = 'Print some image statistics';

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
        $stats = [
            [
                'Images',
                DB::table('images')->count()
            ],[
                'Unseen',
                DB::table('images')->whereNull('last_viewed')->count()
            ],[
                'Derivatives',
                count(Storage::cloud()->allDirectories()) - DB::table('images')->count()
            ],[
                'Orphans',
                DB::table('images')->whereNull('api_key_id')->count()
            ]
        ];

        $this->table(['Parameter', 'Value'], $stats);
    }

}


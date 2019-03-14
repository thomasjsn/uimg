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
                'Not accessed',
                DB::table('images')->whereNull('accessed')->count()
            ],[
                'Derivatives',
                count(Storage::cloud()->allDirectories()) - DB::table('images')->count()
            ]
        ];

        $this->table(['Parameter', 'Value'], $stats);
    }

}


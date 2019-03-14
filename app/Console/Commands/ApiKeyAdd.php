<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use DB;

class ApiKeyAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apikey:add {{--comment=}}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and add new API key';

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
        $key = bin2hex(random_bytes(config('uimg.api_key_length')));

        DB::table('api_keys')->insert([
            'api_key' => $key,
            'comment' => $this->option('comment'),
            'created' => Carbon::now()
        ]);

        $this->info($key);
    }

}


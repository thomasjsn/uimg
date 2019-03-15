<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use DB;

class ApiKeyRemove extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apikey:remove {api_key : API key to remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove specified API key';

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
        $key = $this->argument('api_key');

        DB::table('api_keys')->where(['api_key' => $key])->delete();

        $this->info($key . ' removed');
    }

}


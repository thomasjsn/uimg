<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Redis;

class ApiKeyAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apikey:add
                            {comment : Key description or owner}
                            {{--expire= : Days until key expires}}';

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
        $key = bin2hex(random_bytes(16));

        Redis::set('apikey:' . $key, $this->argument('comment'));

        if ($this->option('expire')) {
            Redis::expire('apikey:' . $key, $this->option('expire') * 60*60*24);
        }

        $this->info($key);
    }

}


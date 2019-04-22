<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class ApiKeyList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apikey:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all API keys';

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
        $apikeys = [];

        $allResults = $this->scanAllForMatch('apikey:*');
        foreach ($allResults as $key) {
            $ttl = Redis::ttl($key);

            $apikeys[] = [
                str_replace('apikey:', '', $key),
                Redis::get($key),
                $ttl > 0 ? Carbon::now()->addSeconds($ttl)->diffForHumans() : -1
            ];
        }

        $this->table(['API key', 'Comment', 'TTL'], $apikeys);
    }

}


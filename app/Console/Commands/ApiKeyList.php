<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use DB;

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
        $keys = DB::table('api_keys')->orderBy('id')->get();

        $keys = $keys->map(function ($key) {
            $lastSeen = 'Never';
            if (! is_null($key->last_seen)) {
                $lastSeen = Carbon::createFromTimeString($key->last_seen)->diffForHumans();
            }

            return [
                $key->api_key,
                $lastSeen,
                $key->comment
            ];
        });

        $this->table(['API key', 'Last seen', 'Comment'], $keys);
    }

}


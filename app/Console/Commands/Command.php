<?php

namespace App\Console\Commands;

use Illuminate\Console\Command as BaseCommand;
use Illuminate\Support\Facades\Redis;

class Command extends BaseCommand
{

    protected function scanAllForMatch ($pattern, $cursor=null, $allResults=array())
    {
		// Zero means full iteration
		if ($cursor==="0") {
			return $allResults;
		}

		// No $cursor means init
		if ($cursor===null) {
			$cursor = "0";
		}

		// The call
		$result = Redis::scan($cursor, 'match', $pattern);

		// Append results to array
		$allResults = array_merge($allResults, $result[1]);

		// Recursive call until cursor is 0
		return $this->scanAllForMatch($pattern, $result[0], $allResults);
	}

}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\urlChecker;
use Illuminate\Support\Facades\DB;

class urlBuilder
{
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $clients = DB::table('account_names')->where([
            ['urlCheck', '=', TRUE],
        ])->select('accountId', 'loginCustomerId', 'name')->get();
        foreach($clients as $client) {
            urlChecker::dispatch($client->accountId, $client->loginCustomerId, $client->name);
        };
    }
}

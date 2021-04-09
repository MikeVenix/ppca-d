<?php

namespace App\Jobs;
use App\Jobs\BudgetFetch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;


class BudgetBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            ['budgetFetch', '=', TRUE],
        ])->select('accountId', 'loginCustomerId', 'name')->get();

        foreach($clients as $client) {
            BudgetFetch::dispatch($client->accountId, $client->loginCustomerId, $client->name);
        };
    }
}

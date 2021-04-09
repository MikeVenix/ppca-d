<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V4\GoogleAdsClientBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Google\Ads\GoogleAds\Lib\V4\GoogleAdsException;
use Google\Ads\GoogleAds\Util\V4\ResourceNames;
use Google\Ads\GoogleAds\V4\Enums\KeywordMatchTypeEnum\KeywordMatchType;
use App\Jobs\KeywordChecker;

class adGroupBuilder
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
            ['keywordCheck', '=', TRUE],
        ])->select('accountId', 'loginCustomerId', 'name')->get();

        foreach($clients as $client) {
            KeywordChecker::dispatch($client->accountId, $client->loginCustomerId, $client->name,)->onQueue('keywords');
        }

    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V4\GoogleAdsClientBuilder;
use Illuminate\Support\Facades\DB;

class impressionFetch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $campaignId;
    private $accountId;
    private $loginCustomerId;
    private $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $campaignId, $accountId, $loginCustomerId)
    {
        $this->id = $id;
        $this->campaignId = $campaignId;
        $this->accountId = $accountId;
        $this->loginCustomerId = $loginCustomerId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $clientId = "--";
        $clientSecret = "--";
        $refreshToken = "--";
        $developerToken = "--";

        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId($clientId)
            ->withClientSecret($clientSecret)
            ->withRefreshToken($refreshToken)
            ->build()
        ;

        $googleAdsClient = (new GoogleAdsClientBuilder())
            ->withDeveloperToken($developerToken)
            ->withLoginCustomerId($this->loginCustomerId)
            ->withOAuth2Credential($oAuth2Credential)
            ->build()
        ;

        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

        $campaignId = $this->campaignId;

        $query = "SELECT metrics.absolute_top_impression_percentage, metrics.search_impression_share FROM campaign WHERE campaign.id = $campaignId";
        $stream = $googleAdsServiceClient->searchStream($this->accountId, $query);
        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $metric = $googleAdsRow->getMetrics()->getAbsoluteTopImpressionPercentage()->getValue();
            $share = $googleAdsRow->getMetrics()->getSearchImpressionShare()->getValue();

            $metric = round($metric, 2);
            $share = round($share, 2);

            DB::table('actions')
                ->where('id', $this->id)
                ->update([
                    'searchImp' => $metric,
                    'absoluteImp' => $share
                    ]);
        };
    }
}

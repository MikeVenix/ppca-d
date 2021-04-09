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
use App\Mail\KeywordAlert;

class KeywordChecker
{
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($accountId, $loginCustomerId, $name)
    {
        $this->accountId = $accountId;
        $this->loginCustomerId = $loginCustomerId;
        $this->name = $name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $emails = ['--','--', '--', '--'];

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

        $words = array();
        $n = 0;

        // $query = "SELECT ad_group_criterion.keyword.text FROM ad_group_criterion WHERE ad_group_criterion.keyword.match_type = BROAD and ad_group.id = $this->id";
        $query = "SELECT campaign_criterion.keyword.text, campaign.id FROM campaign_criterion where campaign_criterion.keyword.match_type = 'BROAD' and campaign_criterion.status = 'ENABLED'";
        $stream = $googleAdsServiceClient->searchStream($this->accountId, $query);
        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $keyWords = explode(' ', $googleAdsRow->getCampaignCriterion()->getKeyword()->getText()->getValue());
            $id = $googleAdsRow->getCampaign()->getIdUnwrapped();
            foreach ($keyWords as $check) {
                if($check[0] !== "+") {
                    array_push ($words, $check, " in ", $id);
                    $n + 1;
                }
            };
            array_push ($words, "<br/><br/>");
        };
        if($n !== 0) {
            Mail::to($emails)->send(new KeywordAlert($id, $this->name, $words));
        }
    }
}

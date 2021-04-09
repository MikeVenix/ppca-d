<?php

namespace App\Http\Controllers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V4\GoogleAdsClientBuilder;
use Illuminate\Support\Facades\DB;
use App\Mail\BudgetAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Google\Ads\GoogleAds\Lib\V4\GoogleAdsException;
use Google\Ads\GoogleAds\Util\V4\ResourceNames;
use App\Jobs\enableCampaign;
use Google\Ads\GoogleAds\V4\Enums\KeywordMatchTypeEnum\KeywordMatchType;


class APIController extends Controller
{
    public function index() {
        $clientId = "--";
        $clientSecret = "--";
        $refreshToken = "--";
        $developerToken = "--";
        $loginCustomerId = "--";
        $accountId = "--";

        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId($clientId)
            ->withClientSecret($clientSecret)
            ->withRefreshToken($refreshToken)
            ->build()
        ;

        $googleAdsClient = (new GoogleAdsClientBuilder())
            ->withDeveloperToken($developerToken)
            ->withLoginCustomerId($loginCustomerId)
            ->withOAuth2Credential($oAuth2Credential)
            ->build()
        ;

        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
        $query = 'SELECT campaign.id, campaign.name, campaign.status, metrics.cost_micros, campaign_budget.amount_micros, metrics.search_impression_share FROM campaign WHERE campaign.serving_status = SERVING AND segments.date DURING TODAY ORDER BY campaign.id';

        $stream = $googleAdsServiceClient->searchStream($accountId, $query);
        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $campaignName = $googleAdsRow->getCampaign()->getName()->getValue();
            $campaignBudget = $googleAdsRow->getCampaignBudget()->getAmountMicros()->getValue();
            $campaignSpend = $googleAdsRow->getMetrics()->getCostMicrosUnwrapped();

            $budget = $campaignBudget / 1000000;
            $spend = $campaignSpend / 1000000;
            echo($campaignName);
            echo("<br/>");
            echo($budget);
            echo("<br/>");
            echo($spend);
            echo("<br/>");
            echo("<br/>");
        };

        // $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

        // $campaignId = 1359064066;

        // $metric = 0;
        // $share = 0;

        // $query = "SELECT metrics.absolute_top_impression_percentage, metrics.search_impression_share FROM campaign WHERE campaign.id = $campaignId";
        // $stream = $googleAdsServiceClient->searchStream($accountId, $query);
        // foreach ($stream->iterateAllElements() as $googleAdsRow) {
        //     $metric = $googleAdsRow->getMetrics()->getAbsoluteTopImpressionPercentage()->getValue();
        //     $share = $googleAdsRow->getMetrics()->getSearchImpressionShare()->getValue();
        // };
        // echo($metric);
        // echo("<br/>");
        // echo($share);
    }
}

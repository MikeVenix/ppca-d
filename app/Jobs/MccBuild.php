<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V3\GoogleAdsClientBuilder;
use Illuminate\Support\Facades\DB;
use App\Jobs\BudgetFetch;

class MccBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $customerId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $customerId = $this->customerId;
        $clientId = "--";
        $clientSecret = "--";
        $refreshToken = "--";
        $developerToken = "--";
        $loginCustomerId = "";

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
        // Creates a query that retrieves active the accounts under the MCC.

        $mccQuery = "SELECT customer.id, customer_client.id, customer_client.descriptive_name FROM customer_client";

        $stream = $googleAdsServiceClient->searchStream($customerId, $mccQuery);

        $googleAdsClient = (new GoogleAdsClientBuilder())
            ->withDeveloperToken($developerToken)
            ->withLoginCustomerId($customerId)
            ->withOAuth2Credential($oAuth2Credential)
            ->build()
        ;

        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $customerClient = $googleAdsRow->getCustomerClient();
            $customerId = $customerClient->getIdUnwrapped();
            BudgetFetch::dispatch($customerId);
        };
    }
}

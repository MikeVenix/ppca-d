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
use Google\Ads\GoogleAds\V4\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\Lib\V4\GoogleAdsClient;
use Google\Ads\GoogleAds\V4\Resources\Campaign;
use Google\Ads\GoogleAds\V4\Services\CampaignOperation;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V4\ResourceNames;

class enableCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $campaignId;
    private $campaignName;
    private $campaignBudget;
    private $accountId;
    private $accountName;
    private $loginCustomerId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($campaignId, $campaignName, $campaignBudget, $accountId, $accountName, $loginCustomerId)
    {
        $this->campaignId = $campaignId;
        $this->campaignName = $campaignName;
        $this->campaignBudget = $campaignBudget;
        $this->accountId = $accountId;
        $this->accountName = $accountName;
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

        try{
            DB::table('actions')->insert([
                'type' => 'campaignEnabled',
                'accountId' => $this->accountId,
                'campaignId' => $this->campaignId,
                'date' => (new \DateTime())->format("Y-m-d"),
                'time' => new \DateTime('now', new \DateTimeZone("GMT")),
                'hash' => md5(json_encode([
                    'budget' => $this->campaignBudget / 1000000,
                ])),
                'data' => json_encode([
                    'budget' => $this->campaignBudget / 1000000,
                    'accountName' => $this->accountName,
                    'campaignName' => $this->campaignName,
                ]),
            ]);

            $campaign = new Campaign([
                'resource_name' => ResourceNames::forCampaign($this->accountId, $this->campaignId),
                'status' => CampaignStatus::ENABLED
            ]);

            $campaignOperation = new CampaignOperation();
            $campaignOperation->setUpdate($campaign);
            $campaignOperation->setUpdateMask(FieldMasks::allSetFieldsOf($campaign));

            $campaignServiceClient = $googleAdsClient->getCampaignServiceClient();
            $response = $campaignServiceClient->mutateCampaigns(
                $this->accountId,
                [$campaignOperation]
            );

        }catch (\Illuminate\Database\QueryException $e){
            $errorCode = $e->errorInfo[1];
            if($errorCode !== 1062){
                throw $e;
            }
        }
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V4\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V4\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\V4\Enums\CampaignStatusEnum\CampaignStatus;
use Illuminate\Support\Facades\DB;
use App\Mail\BudgetAlert;
use App\Mail\errorReport;
use Illuminate\Support\Facades\Mail;
use App\Jobs\enableCampaign;
use App\Jobs\impressionFetch;
use Google\Ads\GoogleAds\V4\Resources\Campaign;
use Google\Ads\GoogleAds\V4\Services\CampaignOperation;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V4\ResourceNames;
use Illuminate\Support\Carbon;
use Throwable;


class BudgetFetch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $accountId;
    private $loginCustomerId;
    private $accountName;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($accountId, $loginCustomerId, $accountName = "")
    {
        $this->accountId = $accountId;
        $this->loginCustomerId = $loginCustomerId;
        $this->accountName = $accountName;
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
        // Creates a query that retrieves active campaigns under the given account.
        $query = 'SELECT campaign.id, campaign.name, campaign.status, metrics.cost_micros, campaign_budget.amount_micros, metrics.search_impression_share FROM campaign WHERE campaign.serving_status = SERVING AND segments.date DURING TODAY ORDER BY campaign.id';
        // Issues a search stream request.
        /** @var GoogleAdsServerStreamDecorator $stream */
        $stream = $googleAdsServiceClient->searchStream($this->accountId, $query);

        // Iterates over all rows in all messages checks them for budget being hit or not
        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            /** @var GoogleAdsRow $googleAdsRow */
            $campaignBudget = $googleAdsRow->getCampaignBudget()->getAmountMicros()->getValue();
            $campaignSpend = $googleAdsRow->getMetrics()->getCostMicrosUnwrapped();

            // $campaignStatus = $googleAdsRow->getCampaign()->getStatus();

            if($campaignSpend >= $campaignBudget){
                $this->sendEmailAlert($googleAdsRow);
                $this->pauseCampaign($googleAdsRow);

                $campaignId = (string) $googleAdsRow->getCampaign()->getIdUnwrapped();
                $campaignName = $googleAdsRow->getCampaign()->getName()->getValue();
                $campaignBudget = $googleAdsRow->getCampaignBudget()->getAmountMicros()->getValue();
                enableCampaign::dispatch($campaignId, $campaignName, $campaignBudget, $this->accountId, $this->accountName, $this->loginCustomerId)->delay(new \DateTime('tomorrow', new \DateTimeZone('Europe/London')));
            }
        }
    }

    protected function sendEmailAlert($googleAdsRow){
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

        $campaignId = (string) $googleAdsRow->getCampaign()->getIdUnwrapped();
        $campaignName = $googleAdsRow->getCampaign()->getName()->getValue();
        $campaignBudget = $googleAdsRow->getCampaignBudget()->getAmountMicros()->getValue();
        $campaignSpend = $googleAdsRow->getMetrics()->getCostMicrosUnwrapped();
        $email =  DB::table('account_names')->where('accountId', '=', $this->accountId)->pluck('managerEmail');

        $weekMap = [
            0 => 'SUNDAY',
            1 => 'MONDAY',
            2 => 'TUESDAY',
            3 => 'WEDNESDAY',
            4 => 'THURSDAY',
            5 => 'FRIDAY',
            6 => 'SATURDAY',
        ];
        $dayOfTheWeek = Carbon::now()->dayOfWeek;
        $weekday = $weekMap[$dayOfTheWeek];

        $query = "SELECT campaign.id, campaign.name, campaign_criterion.type, campaign_criterion.status,
        campaign_criterion.ad_schedule.start_hour, campaign_criterion.ad_schedule.start_minute,
        campaign_criterion.ad_schedule.end_hour, campaign_criterion.ad_schedule.end_minute
            FROM campaign_criterion
            WHERE campaign.id = $campaignId and campaign_criterion.type = AD_SCHEDULE and campaign_criterion.ad_schedule.day_of_week = $weekday";
        $stream = $googleAdsServiceClient->searchStream($this->accountId, $query);

        $SHour = "";
        $EHour = "";
        $STime = "";
        $ETime = "";
        $times = array();
        $start = 0;
        $end = 0;

        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $obj = $googleAdsRow->getCampaignCriterion()->getAdSchedule();

            $SMin = $obj->getStartMinute();
            $SHour = $obj->getStartHour()->getValue();
            $EMin = $obj->getEndMinute();
            $EHour = $obj->getEndHour()->getValue();

            switch ($SMin){
                case 2:
                    $SMin = "00";
                    break;
                case 3:
                    $SMin = "15";
                    break;
                case 4:
                    $SMin = "30";
                    break;
                case 5:
                    $SMin = "45";
                    break;
            };
            switch ($EMin){
                case 2:
                    $EMin = "00";
                    break;
                case 3:
                    $EMin = "15";
                    break;
                case 4:
                    $EMin = "30";
                    break;
                case 5:
                    $EMin = "45";
                    break;
            };

            if($start == 0) {
                $start = $SHour;
            }elseif ($SHour < $start) {
                $start = $SHour;
            }

            if($end == 0) {
                $end = $EHour;
            }elseif ($EHour > $end) {
                $end = $EHour;
            }

            $time = $start . ":" . $SMin . " to " . $end . ":" . $EMin;
            $STime = $start . ":" . $SMin;
            $ETime = $end . ":" . $EMin;

            $times[] = $time;
        };

        try{
            $SHour = (int)$start;
            $EHour = (int)$end;
            (int)$hour = date('H');
            $runTime = $hour - $SHour;
            $budget = $campaignBudget / 1000000;
            $hourSpend = $budget / $runTime;
            $scheduleTime = $EHour - $SHour;
            $budgetGuess = $hourSpend * $scheduleTime;
            $fullSchedule = $start . " to " . $end;

            $id = DB::table('actions')->insertGetId([
                'type' => 'hitBudget',
                'accountId' => $this->accountId,
                'campaignId' => $campaignId,
                'date' => (new \DateTime())->format("Y-m-d"),
                'time' => new \DateTime('now', new \DateTimeZone("GMT")),
                'hash' => md5(json_encode([
                    'budget' => $campaignBudget / 1000000,
                ])),
                'data' => json_encode([
                    'budget' => $budget,
                    'spend' => $campaignSpend / 1000000,
                    'accountName' => $this->accountName,
                    'campaignName' => $campaignName,
                    'scheduleStart' => $STime,
                    'scheduleEnd' => $ETime,
                    'suggestedBudget' => $budgetGuess,
                    'times' => $times,
                    'fullschedule' => $fullSchedule,
                ]),
            ]);

            DB::table('actions')
            ->where('id', $id)
            ->update([
                'searchImp' => 1,
                'absoluteImp' => 1
            ]);

            Mail::to($email)->cc(['--','--'])->send(new BudgetAlert($this->accountName, $campaignName, $campaignBudget, $campaignSpend));

            $now = Carbon::now();
            $delay = Carbon::tomorrow('Europe/London')->subMinutes(15);

            $time = $now->diffInMinutes($delay);
            impressionFetch::Dispatch($id, $campaignId, $this->accountId, $this->loginCustomerId)
                ->delay(now()->addMinutes(30));

        }catch (\Illuminate\Database\QueryException $e){
            $errorCode = $e->errorInfo[1];
            if($errorCode !== 1062){
                throw $e;
            }
        }
    }

    protected function pauseCampaign($googleAdsRow){
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

        $campaignId = (string) $googleAdsRow->getCampaign()->getIdUnwrapped();
        $campaignName = $googleAdsRow->getCampaign()->getName()->getValue();
        $campaignBudget = $googleAdsRow->getCampaignBudget()->getAmountMicros()->getValue();

        try{
            DB::table('actions')->insert([
                'type' => 'campaignPaused',
                'accountId' => $this->accountId,
                'campaignId' => $campaignId,
                'date' => (new \DateTime())->format("Y-m-d"),
                'time' => new \DateTime('now', new \DateTimeZone("GMT")),
                'hash' => md5(json_encode([
                    'budget' => $campaignBudget / 1000000,
                ])),
                'data' => json_encode([
                    'budget' => $campaignBudget / 1000000,
                    'accountName' => $this->accountName,
                    'campaignName' => $campaignName,
                ]),
            ]);

            $campaign = new Campaign([
                'resource_name' => ResourceNames::forCampaign($this->accountId, $campaignId),
                'status' => CampaignStatus::PAUSED
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

    protected function enableCampaign($googleAdsRow){
        $campaignId = (string) $googleAdsRow->getCampaign()->getIdUnwrapped();
        $campaignName = $googleAdsRow->getCampaign()->getName()->getValue();
        $campaignBudget = $googleAdsRow->getCampaignBudget()->getAmountMicros()->getValue();

        try{
            DB::table('actions')->insert([
                'type' => 'campaignEnabled',
                'accountId' => $this->accountId,
                'campaignId' => $campaignId,
                'date' => (new \DateTime())->format("Y-m-d"),
                'time' => new \DateTime('now', new \DateTimeZone("GMT")),
                'hash' => md5(json_encode([
                    'budget' => $campaignBudget / 1000000,
                ])),
                'data' => json_encode([
                    'budget' => $campaignBudget / 1000000,
                    'accountName' => $this->accountName,
                    'campaignName' => $campaignName,
                ]),
            ]);
        }catch (\Illuminate\Database\QueryException $e){
            $errorCode = $e->errorInfo[1];
            if($errorCode !== 1062){
                throw $e;
            }
        }
    }

    protected function wasPausedYesterday($googleAdsRow){
        $campaignId = (string) $googleAdsRow->getCampaign()->getIdUnwrapped();
        return (bool) DB::table("actions")->where([
            ['accountId', '=', $this->accountId],
            ['campaignId', '=', $campaignId],
            ['type', '=', 'campaignPaused'],
            ['date', '=', (new \DateTime('yesterday'))->format('Y-m-d')],
        ])->first();;
    }

    public function failed(Throwable $exception)
    {
        Mail::to(['--'])->send(new errorReport($exception));
    }
}


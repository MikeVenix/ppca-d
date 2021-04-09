<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V4\GoogleAdsClientBuilder;
use Illuminate\Support\Facades\Mail;
use Google\Ads\GoogleAds\Lib\V4\GoogleAdsException;
use App\Mail\urlAlert;

class urlChecker
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
        $clientId = "--";
        $clientSecret = "--";
        $refreshToken = "--";
        $developerToken = "--";

        $emails = ['--', '--', '--', '--'];
        //$emails = ['--'];

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
        set_error_handler(null);
        $query = "SELECT expanded_landing_page_view.expanded_final_url, campaign.status, campaign.id, campaign.name, campaign.serving_status FROM landing_page_view";
        $stream = $googleAdsServiceClient->searchStream($this->accountId, $query);

        // god what do I even say about this hot mess... SO I couldn't do a where enabled because the API decided it didn't want to do it (segmented data be damned),
        // and the clunky Laravel error handler crashed out if I didn't cobble together this hot mess of a replacement for it. I'm so sorry if you have to fiddle with this later I did the best I could
        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $errors = [];
            $previousErrorHandler = set_error_handler(function($level, $message, $file = '', $line = 0, $context = []) use (&$errors) {
                $errors[] = $message;
            });

            $stat = $googleAdsRow->getCampaign()->getStatus();
            $serv = $googleAdsRow->getCampaign()->getServingStatus();

            if($stat == 2 && $serv == 2){
                $campaignName = $googleAdsRow->getCampaign()->getName()->getValue();
                $url = $googleAdsRow->getExpandedLandingPageView()->getExpandedFinalUrl()->getValue();
                try{
                    $errors = [];
                    $header = get_headers($url);
                    if ($errors) {
                        Mail::to('--')->cc(['--'])->send(new urlAlert($this->name, $errors, $campaignName, $url));
                        print_r($errors);
                    } else if($header && strpos( $header[0], '200')){
                        print_r("  Has Passed");
                    } else{
                        print_r($header[0]);
                        $errors = $header[0];
                        Mail::to($emails)->send(new urlAlert($this->name, $errors, $campaignName, $url));
                    };
                }catch (Exception $e) {
                    echo($e);
                }
                echo("<br/><br/>");
            };
            set_error_handler($previousErrorHandler);
        };
    }
}

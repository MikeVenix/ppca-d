<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class urlAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $errors, $campaignName, $url)
    {
        $this->name = $name;
        $this->errors = $errors;
        $this->campaignName = $campaignName;
        $this->url = $url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('alerts@ppc-assist.website')
                    ->view('mail.url')
                    ->subject('Broken Target URLs')
                    ->with([
                        'name' => $this->name,
                        'errors' => $this->errors,
                        'campaignName' => $this->campaignName,
                        'url' => $this->url,
                    ]);
    }
}

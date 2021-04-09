<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KeywordAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($id, $name, $words)
    {
        $this->id = $id;
        $this->name = $name;
        $this->words = $words;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $name = $this->name;
        $id = $this->id;
        $words = $this->words;
        return $this->from('alerts@ppc-assist.website')
                    ->view('mail.keyword')
                    ->subject('Broad Match Keywords')
                    ->with([
                        'name' => $name,
                        'id' => $id,
                        'words' => $words,
                    ]);
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class BudgetAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**p
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($account, $name, $budget, $spend)
    {
        $this->account = $account;
        $this->name = $name;
        $this->budget = $budget;
        $this->spend = $spend;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $account = $this->account;
        $name = $this->name;
        $budget = $this->budget / 1000000;
        $spend = $this->spend / 1000000;
        $time = Carbon::now();

        return $this->from('alerts@ppc-assist.website')
                    ->view('mail.budget')
                    ->with([
                        'account' => $account,
                        'name' => $name,
                        'budget' => $budget,
                        'spend' => $spend,
                        'time' => $time
                    ]);
    }
}

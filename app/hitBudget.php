<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class hitBudget extends Model
{
    protected $fillable = [
        'time','accountName','campaignName', 'adSchedule', 'budget', 'spend', 'searchImpShare'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id', 'metric', 'value', 'recorded_on',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'recorded_on' => 'date',
    ];

    public function channel()
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SuppressionList extends Model
{
    protected $table = 'suppression_list';

    protected $fillable = [
        'uuid', 'email', 'email_hash', 'reason', 'source',
        'organization_id', 'campaign_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (SuppressionList $entry) {
            $entry->uuid = (string) Str::uuid();
            $entry->email = strtolower(trim((string) $entry->email));
            $entry->email_hash = sha1($entry->email);
        });
    }
}

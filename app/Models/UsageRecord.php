<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageRecord extends Model
{
    use HasFactory, TenantAware;
}
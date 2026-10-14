<?php

namespace StlHorizon\Sms\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'response' => 'array',
        'success' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('sms.logging.table', 'sms_logs');
    }
}

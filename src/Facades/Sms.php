<?php

namespace StlHorizon\Sms\Facades;

use Illuminate\Support\Facades\Facade;
use StlHorizon\Sms\Services\SmsManager;

class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmsManager::class;
    }
}

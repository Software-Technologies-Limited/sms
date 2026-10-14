<?php

namespace StlHorizon\Sms\Tests;

use Illuminate\Notifications\Notification;
use StlHorizon\Sms\Notifications\SmsChannel;
use StlHorizon\Sms\Services\SmsManager;

class SmsChannelTest extends TestCase
{
    public function test_channel_sends_message(): void
    {
        $channel = new SmsChannel($this->app->make(SmsManager::class));

        $response = $channel->send(new TestNotifiable, new TestNotification);

        $this->assertTrue($response['success']);
        $this->assertSame('Hello from notification.', $response['data']['message']);
        $this->assertSame('+254711111111', $response['data']['to']);
    }
}

class TestNotifiable
{
    public string $phone = '+254711111111';
}

class TestNotification extends Notification
{
    public function toSms($notifiable): string
    {
        return 'Hello from notification.';
    }
}

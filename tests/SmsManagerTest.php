<?php

namespace StlHorizon\Sms\Tests;

use StlHorizon\Sms\Services\SmsManager;

class SmsManagerTest extends TestCase
{
    public function test_send_template_renders_and_logs(): void
    {
        $manager = $this->app->make(SmsManager::class);

        $response = $manager->sendTemplate('compliance_notice', '+254700000000', [
            'name' => 'Amina',
            'status' => 'COMPLIANT',
        ]);

        $this->assertTrue($response['success']);
        $this->assertSame('Hello Amina, your status is COMPLIANT.', $response['data']['message']);

        $this->assertDatabaseCount('sms_logs', 1);
        $this->assertDatabaseHas('sms_logs', [
            'recipient' => '+254700000000',
            'message' => 'Hello Amina, your status is COMPLIANT.',
            'success' => 1,
        ]);
    }
}

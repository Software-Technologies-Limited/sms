<?php

namespace StlHorizon\Sms\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use StlHorizon\Sms\Services\AfricasTalkingSms;

class AfricasTalkingSmsTest extends TestCase
{
    private function driver(): AfricasTalkingSms
    {
        return new AfricasTalkingSms([
            'username' => 'sandbox', 'api_key' => 'test-key',
            'endpoint' => 'https://api.sandbox.africastalking.com/version1/messaging', 'from' => 'EBOARD',
        ]);
    }

    public function test_direct_send_returns_gateway_details_and_protects_the_recipient_payload(): void
    {
        Http::preventStrayRequests();
        $recipient = ['number' => '+254700000000', 'status' => 'Success', 'statusCode' => 101, 'messageId' => 'AT-test', 'cost' => 'KES 1'];
        Http::fake(['*' => Http::response(['SMSMessageData' => ['Recipients' => [$recipient]]], 201)]);
        $response = $this->driver()->send([' +254700000000 ', '+254700000000'], 'Evaluation reminder', [
            'username' => 'override', 'to' => '+254799999999', 'message' => 'override', 'enqueue' => 1,
        ]);
        $this->assertTrue($response['success']);
        $this->assertSame([$recipient], $response['recipients']);
        Http::assertSent(fn (Request $request): bool => $request['username'] === 'sandbox'
            && $request['to'] === '+254700000000' && $request['message'] === 'Evaluation reminder'
            && $request['from'] === 'EBOARD' && ! isset($request['enqueue']) && $request->hasHeader('apiKey', 'test-key'));
        Http::assertSentCount(1);
    }

    public function test_http_success_with_a_rejected_recipient_is_a_failed_send(): void
    {
        Http::fake(['*' => Http::response(['SMSMessageData' => ['Recipients' => [
            ['number' => '+254700000000', 'statusCode' => 101],
            ['number' => '+254700000001', 'statusCode' => 403, 'status' => 'InvalidPhoneNumber'],
        ]]], 201)]);
        $response = $this->driver()->send('+254700000000,+254700000001', 'Reminder');
        $this->assertFalse($response['success']);
        $this->assertCount(2, $response['recipients']);
    }

    public function test_gateway_success_code_100_is_accepted(): void
    {
        Http::fake(['*' => Http::response(['SMSMessageData' => ['Recipients' => [
            ['number' => '+254700000000', 'status' => 'Success', 'statusCode' => 100, 'messageId' => 'AT-100'],
        ]]], 201)]);
        $this->assertTrue($this->driver()->send('+254700000000', 'Reminder')['success']);
    }

    public function test_empty_gateway_response_is_not_a_success(): void
    {
        Http::fake(['*' => Http::response('Unexpected response', 200)]);
        $this->assertFalse($this->driver()->send('+254700000000', 'Reminder')['success']);
    }

    public function test_connection_failure_returns_a_result_without_retrying(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $response = $this->driver()->send('+254700000000', 'Reminder');
        $this->assertFalse($response['success']);
        $this->assertSame(0, $response['status']);
        $this->assertSame([], $response['recipients']);
    }

    public function test_blank_recipients_are_rejected_before_sending(): void
    {
        Http::fake();
        try {
            $this->driver()->send(' , ', 'Reminder');
            $this->fail('Empty recipients must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('SMS recipients and message are required.', $exception->getMessage());
        }
        Http::assertNothingSent();
    }
}

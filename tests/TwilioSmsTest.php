<?php

namespace StlHorizon\Sms\Tests;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use StlHorizon\Sms\Services\TwilioSms;

class TwilioSmsTest extends TestCase
{
    public function test_send_single_message(): void
    {
        Http::fake(function ($request) {
            $this->assertSame('https://api.twilio.com/2010-04-01/Accounts/AC123/Messages.json', $request->url());
            $this->assertSame('auth-token', $this->basicAuthPassword($request));

            $data = $request->data();
            $this->assertSame('+254740289578', $data['To']);
            $this->assertSame('Hello', $data['Body']);
            $this->assertSame('+25439224658', $data['From']);

            return Http::response(['sid' => 'SM123'], 201);
        });

        $driver = new TwilioSms([
            'account_sid' => 'AC123',
            'auth_token' => 'auth-token',
            'from' => '+25439224658',
            'timeout' => 10,
        ]);

        $response = $driver->send('+254740289578', 'Hello');

        $this->assertTrue($response['success']);
        $this->assertSame(201, $response['status']);
        $this->assertSame('SM123', $response['data']['sid']);
    }

    public function test_send_multiple_recipients(): void
    {
        Http::fakeSequence()
            ->push(['sid' => 'SM1'], 201)
            ->push(['sid' => 'SM2'], 201);

        $driver = new TwilioSms([
            'account_sid' => 'AC123',
            'auth_token' => 'auth-token',
            'from' => '+25439224658',
        ]);

        $response = $driver->send(['+25439224658', '+254740289578'], 'Hello');

        Http::assertSentCount(2);
        $this->assertTrue($response['success']);
        $this->assertSame(200, $response['status']);
        $this->assertCount(2, $response['data']);
    }

    public function test_missing_to_throws(): void
    {
        $driver = new TwilioSms([
            'account_sid' => 'AC123',
            'auth_token' => 'auth-token',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $driver->send('', 'Hello');
    }

    public function test_missing_credentials_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $driver = new TwilioSms([
            'account_sid' => '',
            'auth_token' => '',
        ]);

        $driver->send('+25439224658', 'Hello');
    }

    private function basicAuthPassword($request): ?string
    {
        $header = $request->header('Authorization')[0] ?? null;
        if (! $header || ! str_starts_with($header, 'Basic ')) {
            return null;
        }

        $decoded = base64_decode(substr($header, 6));
        if (! $decoded || ! str_contains($decoded, ':')) {
            return null;
        }

        [, $password] = explode(':', $decoded, 2);

        return $password;
    }
}

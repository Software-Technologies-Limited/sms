<?php

namespace StlHorizon\Sms\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use StlHorizon\Sms\Contracts\SmsDriver;

class TwilioSms implements SmsDriver
{
    public function __construct(private array $config) {}

    public function send(string|array $to, string $message, array $options = []): array
    {
        $this->guardConfig();

        $recipients = $this->normalizeRecipients($to);

        if (count($recipients) === 0) {
            throw new InvalidArgumentException('sms.to is required.');
        }

        if (count($recipients) === 1) {
            return $this->sendSingle($recipients[0], $message, $options);
        }

        $results = [];
        $allSuccess = true;

        foreach ($recipients as $recipient) {
            $result = $this->sendSingle($recipient, $message, $options);
            $results[] = $result;
            $allSuccess = $allSuccess && ($result['success'] ?? false);
        }

        return [
            'success' => $allSuccess,
            'status' => $allSuccess ? 200 : 207,
            'data' => $results,
            'body' => null,
        ];
    }

    private function guardConfig(): void
    {
        if (empty($this->config['account_sid'])) {
            throw new InvalidArgumentException('sms.account_sid is required.');
        }

        if (empty($this->config['auth_token'])) {
            throw new InvalidArgumentException('sms.auth_token is required.');
        }
    }

    private function endpoint(): string
    {
        $base = $this->config['endpoint'] ?? 'https://api.twilio.com/2010-04-01';

        return rtrim($base, '/').'/Accounts/'.$this->config['account_sid'].'/Messages.json';
    }

    private function normalizeRecipients(string|array $to): array
    {
        if (is_array($to)) {
            return array_values(array_filter(array_map('trim', $to)));
        }

        $to = trim($to);

        return $to === '' ? [] : [$to];
    }

    private function sendSingle(string $to, string $message, array $options = []): array
    {
        $payload = array_merge([
            'To' => $to,
            'Body' => $message,
        ], $options);

        if (! empty($this->config['from']) && empty($payload['From'])) {
            $payload['From'] = $this->config['from'];
        }

        if (! empty($this->config['messaging_service_sid']) && empty($payload['MessagingServiceSid'])) {
            $payload['MessagingServiceSid'] = $this->config['messaging_service_sid'];
        }

        $response = Http::timeout($this->config['timeout'] ?? 15)
            ->asForm()
            ->withBasicAuth($this->config['account_sid'], $this->config['auth_token'])
            ->post($this->endpoint(), $payload);

        if ($response instanceof PromiseInterface) {
            $response = $response->wait();
        }

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json(),
            'body' => $response->body(),
        ];
    }
}

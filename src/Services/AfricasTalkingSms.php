<?php

namespace StlHorizon\Sms\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use StlHorizon\Sms\Contracts\SmsDriver;

class AfricasTalkingSms implements SmsDriver
{
    public function __construct(private array $config) {}

    public function send(string|array $to, string $message, array $options = []): array
    {
        $this->guardConfig();

        $recipients = $this->formatRecipients($to);
        if ($recipients === '' || trim($message) === '') {
            throw new InvalidArgumentException('SMS recipients and message are required.');
        }

        foreach (['username', 'to', 'message', 'enqueue'] as $reserved) {
            unset($options[$reserved]);
        }

        $payload = array_merge([
            'username' => $this->config['username'],
            'to' => $recipients,
            'message' => $message,
        ], $this->defaultOptions(), $options);

        if (! empty($this->config['from']) && empty($payload['from'])) {
            $payload['from'] = $this->config['from'];
        }

        try {
            $response = Http::timeout($this->config['timeout'] ?? 15)
                ->asForm()
                ->withHeaders([
                    'apiKey' => $this->config['api_key'],
                    'Accept' => 'application/json',
                ])
                ->post($this->config['endpoint'], $payload);

            if ($response instanceof PromiseInterface) {
                $response = $response->wait();
            }
        } catch (ConnectionException $exception) {
            return [
                'success' => false, 'status' => 0, 'data' => null, 'body' => '',
                'recipients' => [], 'error' => 'SMS gateway connection failed. Delivery could not be confirmed.',
            ];
        }

        $data = $response->json();
        $results = is_array($data) ? ($data['SMSMessageData']['Recipients'] ?? []) : [];
        $results = is_array($results) ? array_values(array_filter($results, 'is_array')) : [];
        $accepted = count(array_filter($results, fn (array $recipient): bool => in_array((int) ($recipient['statusCode'] ?? 0), [100, 101], true)));
        $success = $response->successful() && count($results) === count(explode(',', $recipients)) && $accepted === count($results);

        return [
            'success' => $success,
            'status' => $response->status(),
            'data' => $data,
            'body' => $response->body(),
            'recipients' => $results,
            'error' => $success ? null : 'The SMS gateway did not accept every recipient. Check recipient statuses.',
        ];
    }

    private function guardConfig(): void
    {
        if (empty($this->config['username'])) {
            throw new InvalidArgumentException('sms.username is required.');
        }

        if (empty($this->config['api_key'])) {
            throw new InvalidArgumentException('sms.api_key is required.');
        }

        if (empty($this->config['endpoint'])) {
            throw new InvalidArgumentException('sms.endpoint is required.');
        }
    }

    private function formatRecipients(string|array $to): string
    {
        $recipients = is_array($to) ? $to : explode(',', $to);

        return implode(',', array_unique(array_filter(array_map('trim', $recipients), fn (string $number): bool => $number !== '')));
    }

    private function defaultOptions(): array
    {
        $options = [];

        if (isset($this->config['bulk_mode']) && $this->config['bulk_mode'] !== '') {
            $options['bulkSMSMode'] = (int) $this->config['bulk_mode'];
        }

        return $options;
    }
}

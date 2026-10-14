<?php

namespace StlHorizon\Sms\Services;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use StlHorizon\Sms\Contracts\SmsDriver;
use StlHorizon\Sms\Models\SmsLog;

class SmsManager
{
    private array $drivers = [];

    private array $customCreators = [];

    public function __construct(private Container $app, private array $config) {}

    public function driver(?string $name = null): SmsDriver
    {
        $name = $name ?: $this->getDefaultDriver();

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        if (isset($this->customCreators[$name])) {
            return $this->drivers[$name] = $this->customCreators[$name]($this->app, $this->getDriverConfig($name));
        }

        return $this->drivers[$name] = $this->createDriver($name);
    }

    public function send(string|array $to, string $message, array $options = []): array
    {
        $driverName = $options['driver'] ?? null;
        unset($options['driver']);

        $response = $this->driver($driverName)->send($to, $message, $options);
        $this->logSend($driverName ?: $this->getDefaultDriver(), $to, $message, $response);

        return $response;
    }

    public function sendTemplate(string $templateKey, string|array $to, array $data = [], array $options = []): array
    {
        $template = $this->getTemplate($templateKey);
        $message = $this->renderTemplate($template, $data);

        return $this->send($to, $message, $options);
    }

    public function extend(string $name, Closure $creator): void
    {
        $this->customCreators[$name] = $creator;
        unset($this->drivers[$name]);
    }

    private function createDriver(string $name): SmsDriver
    {
        $config = $this->getDriverConfig($name);

        if (isset($config['class'])) {
            return $this->app->make($config['class'], ['config' => $config]);
        }

        return match ($name) {
            'africastalking' => new AfricasTalkingSms($config),
            'twilio' => new TwilioSms($config),
            default => throw new InvalidArgumentException("SMS driver [{$name}] is not supported."),
        };
    }

    private function getDefaultDriver(): string
    {
        return $this->config['default'] ?? 'africastalking';
    }

    private function getDriverConfig(string $name): array
    {
        $drivers = $this->config['drivers'] ?? [];

        if (isset($drivers[$name])) {
            return $drivers[$name];
        }

        // Backwards compatibility if someone still uses top-level config.
        if ($name === 'africastalking') {
            return $this->config;
        }

        return [];
    }

    private function getTemplate(string $templateKey): string
    {
        $templates = $this->config['templates'] ?? [];

        if (! isset($templates[$templateKey])) {
            throw new InvalidArgumentException("SMS template [{$templateKey}] is not defined.");
        }

        return $templates[$templateKey];
    }

    private function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{'.$key.'}', (string) $value, $template);
        }

        return $template;
    }

    private function logSend(string $driver, string|array $to, string $message, array $response): void
    {
        $logging = $this->config['logging']['enabled'] ?? false;

        if (! $logging) {
            return;
        }

        $table = $this->config['logging']['table'] ?? 'sms_logs';

        if (! Schema::hasTable($table)) {
            return;
        }

        SmsLog::query()->create([
            'driver' => $driver,
            'recipient' => is_array($to) ? implode(',', $to) : $to,
            'message' => $message,
            'success' => $response['success'] ?? false,
            'status' => (string) ($response['status'] ?? ''),
            'response' => $response,
        ]);
    }
}

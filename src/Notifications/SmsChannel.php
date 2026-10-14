<?php

namespace StlHorizon\Sms\Notifications;

use Illuminate\Notifications\Notification;
use InvalidArgumentException;
use StlHorizon\Sms\Services\SmsManager;

class SmsChannel
{
    public function __construct(private SmsManager $sms) {}

    public function send(mixed $notifiable, Notification $notification): ?array
    {
        if (! method_exists($notification, 'toSms')) {
            return null;
        }

        $message = $notification->toSms($notifiable);

        if (is_string($message)) {
            return $this->sms->send($this->route($notifiable), $message);
        }

        if (is_array($message)) {
            if (isset($message['template'])) {
                return $this->sms->sendTemplate(
                    $message['template'],
                    $message['to'] ?? $this->route($notifiable),
                    $message['data'] ?? [],
                    $message['options'] ?? []
                );
            }

            if (isset($message['message'])) {
                return $this->sms->send(
                    $message['to'] ?? $this->route($notifiable),
                    $message['message'],
                    $message['options'] ?? []
                );
            }
        }

        throw new InvalidArgumentException('SmsChannel expects a string or array from toSms().');
    }

    private function route(mixed $notifiable): string
    {
        if (method_exists($notifiable, 'routeNotificationForSms')) {
            return (string) $notifiable->routeNotificationForSms();
        }

        foreach (['phone', 'phone_number', 'mobile', 'msisdn'] as $property) {
            if (isset($notifiable->{$property})) {
                return (string) $notifiable->{$property};
            }
        }

        throw new InvalidArgumentException('Notifiable does not have a phone number.');
    }
}

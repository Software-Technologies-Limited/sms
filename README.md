# software-technologies-limited/sms

Laravel package for sending SMS via Africa's Talking and Twilio.

## Requirements

- PHP 8.1+
- Laravel 10/11/12/13

Repository: [Software-Technologies-Limited/sms](https://github.com/Software-Technologies-Limited/sms).
The PHP namespace `JamesKabz\Sms` remains unchanged for compatibility.

## Installation from Packagist

Once the repository is submitted to Packagist under the new name:

```bash
composer require software-technologies-limited/sms
```

## Installation (local path)

1) Add repository and require the package in your app `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/software-technologies-limited/sms"
    }
  ],
  "require": {
    "software-technologies-limited/sms": "*"
  }
}
```

2) Install:

```bash
composer update software-technologies-limited/sms
```

## Configuration

Publish the config (optional):

```bash
php artisan vendor:publish --tag=sms-config
```

Add credentials to your `.env`:

```
AFRICASTALKING_USERNAME=your_username
AFRICASTALKING_API_KEY=your_api_key
AFRICASTALKING_FROM=your_sender_id
```

Twilio (optional):

```
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM=your_twilio_number
TWILIO_MESSAGING_SERVICE_SID=your_messaging_service_sid
```

Optional overrides:

```
AFRICASTALKING_SMS_ENDPOINT=https://api.africastalking.com/version1/messaging
AFRICASTALKING_TIMEOUT=15
AFRICASTALKING_BULK_MODE=1

SMS_DRIVER=africastalking
SMS_LOGGING=true
SMS_WEBHOOK_ENABLED=false
SMS_WEBHOOK_PATH=sms/webhook
```

## Usage

### Facade

```php
use JamesKabz\Sms\Facades\Sms;

Sms::send('+2547XXXXXXXX', 'Hello from Africa\'s Talking');
```

Sending happens immediately in the current request; no Laravel queue worker is required.
The sandbox username automatically selects the sandbox API endpoint unless an endpoint is explicitly configured.

### Drivers

Set the default driver in `.env`:

```
SMS_DRIVER=africastalking
```

Use Twilio:

```
SMS_DRIVER=twilio
```

To add another driver, define it in `config/sms.php` under `drivers` with a `class` key that implements `JamesKabz\\Sms\\Contracts\\SmsDriver`, then set `SMS_DRIVER` to that name.

### Send with a template

```php
// config/sms.php -> templates['compliance_notice']
Sms::sendTemplate('compliance_notice', '+2547XXXXXXXX', [
    'name' => 'Amina',
    'status' => 'COMPLIANT',
]);
```

### Dependency Injection

```php
use JamesKabz\Sms\Services\SmsManager;

public function send(SmsManager $sms)
{
    $sms->send(['+2547XXXXXXXX', '+2547YYYYYYYY'], 'Hello!');
}
```

### Per-call options

```php
Sms::send('+2547XXXXXXXX', 'Hello', [
    'from' => 'MyBrand',
    'bulkSMSMode' => 1,
]);
```

## Notifications (SmsChannel)

```php
use Illuminate\\Notifications\\Notification;
use JamesKabz\\Sms\\Notifications\\SmsChannel;

class ComplianceNotice extends Notification
{
    public function via($notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms($notifiable): array
    {
        return [
            'template' => 'compliance_notice',
            'data' => ['name' => $notifiable->name, 'status' => $notifiable->status],
        ];
    }
}
```

To resolve the phone number, implement `routeNotificationForSms()` on your notifiable or provide a `phone` / `phone_number` field.

## Logging

Migrations are disabled by default. Installing the package or running your application's
normal migrations will not create the package's table unless you explicitly enable
`SMS_LOAD_MIGRATIONS=true`.

For guided setup, run:

```bash
php artisan sms:install
```

This publishes configuration and asks whether to create the package's SMS log table.
The default answer is **No**. Choose No for eBoard, which already has `ebr_sms_outbox`.
Choosing Yes runs only the package migration on the default database and skips it if the
configured log table already exists. When using this optional table, set `SMS_LOGGING=true`.

## Webhook (delivery reports)

Enable in `.env`:

```
SMS_WEBHOOK_ENABLED=true
SMS_WEBHOOK_PATH=sms/webhook
```

This registers a POST endpoint that stores the payload in `sms_logs` (when logging is enabled).

## Response format

`send()` returns a structured array:

```php
[
    'success' => true,
    'status' => 201,
    'data' => [...],
    'body' => '...'
]
```

The Africa's Talking driver also returns `recipients` and `error`. Each recipient contains the
gateway's `number`, `status`, `statusCode`, `messageId`, and `cost` when supplied.
`success` is true only when the HTTP request succeeds and every recipient is accepted with
status code 100 or 101. Acceptance is not confirmation of handset delivery. Connection failures return
`success = false` and `status = 0`; they are not automatically retried.

For eBoard, retain the existing tenant `ebr_sms_outbox` table as the send log. Map recipient
results to `to`, `status`, `status_code`, `messageId`, and `cost`; the application supplies
`id_user`, `id_company`, `notification_type`, `id`, `message`, `from`, and `time_sent`.
This mapping must be implemented in the consuming application's notification flow.
Disable the package's separate logging table and migration registration when using it:

```dotenv
SMS_LOGGING=false
SMS_LOAD_MIGRATIONS=false
```

## Notes

- Ensure your sender ID (`AFRICASTALKING_FROM`) is approved in Africa's Talking.
- For production, use your live username and API key (not the sandbox).
- Twilio requires either `TWILIO_FROM` or `TWILIO_MESSAGING_SERVICE_SID`.

## Testing (package)

```bash
cd packages/software-technologies-limited/sms
composer install
vendor/bin/phpunit
```

## License

MIT

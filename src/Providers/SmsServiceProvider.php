<?php

namespace JamesKabz\Sms\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use JamesKabz\Sms\Http\Controllers\SmsWebhookController;
use JamesKabz\Sms\Services\AfricasTalkingSms;
use JamesKabz\Sms\Services\SmsManager;
use JamesKabz\Sms\Services\TwilioSms;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/sms.php', 'sms');

        $this->app->singleton(SmsManager::class, function ($app) {
            return new SmsManager($app, $app['config']['sms'] ?? []);
        });

        $this->app->singleton(AfricasTalkingSms::class, function ($app) {
            $config = $app['config']['sms'] ?? [];

            return new AfricasTalkingSms($config['drivers']['africastalking'] ?? []);
        });

        $this->app->singleton(TwilioSms::class, function ($app) {
            $config = $app['config']['sms'] ?? [];

            return new TwilioSms($config['drivers']['twilio'] ?? []);
        });

        $this->app->alias(SmsManager::class, 'sms');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../Config/sms.php' => config_path('sms.php'),
        ], 'sms-config');

        if (config('sms.load_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }

        $this->registerWebhookRoutes();
    }

    private function registerWebhookRoutes(): void
    {
        if (! config('sms.webhook.enabled')) {
            return;
        }

        if ($this->app->routesAreCached()) {
            return;
        }

        $path = config('sms.webhook.path', 'sms/webhook');
        $middleware = config('sms.webhook.middleware', ['api']);

        Route::middleware($middleware)->post($path, SmsWebhookController::class)
            ->name('sms.webhook');
    }
}

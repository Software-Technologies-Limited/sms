<?php

namespace StlHorizon\Sms\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use StlHorizon\Sms\Providers\SmsServiceProvider;
use StlHorizon\Sms\Tests\Fakes\FakeSmsDriver;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [SmsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('sms.logging.enabled', true);
        $app['config']->set('sms.load_migrations', true);
        $app['config']->set('sms.logging.table', 'sms_logs');
        $app['config']->set('sms.default', 'fake');
        $app['config']->set('sms.drivers.fake', [
            'class' => FakeSmsDriver::class,
        ]);
        $app['config']->set('sms.templates', [
            'compliance_notice' => 'Hello {name}, your status is {status}.',
        ]);
    }
}

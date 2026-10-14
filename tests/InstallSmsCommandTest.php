<?php

namespace StlHorizon\Sms\Tests;

use Illuminate\Support\Facades\Schema;

class InstallSmsCommandTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('sms.load_migrations', false);
    }

    public function test_declining_migration_keeps_the_database_unchanged(): void
    {
        $this->artisan('sms:install')
            ->expectsConfirmation('Create the package SMS log table on the default database?', 'no')
            ->assertSuccessful();
        $this->assertFalse(Schema::hasTable('sms_logs'));
    }

    public function test_confirming_creates_the_optional_log_table(): void
    {
        $this->artisan('sms:install')
            ->expectsConfirmation('Create the package SMS log table on the default database?', 'yes')
            ->assertSuccessful();
        $this->assertTrue(Schema::hasTable('sms_logs'));
    }

    public function test_existing_log_table_is_preserved(): void
    {
        Schema::create('sms_logs', function ($table): void {
            $table->string('existing_column');
        });
        $this->artisan('sms:install')
            ->expectsConfirmation('Create the package SMS log table on the default database?', 'yes')
            ->expectsOutput('The configured SMS log table already exists. Migration skipped.')
            ->assertSuccessful();
        $this->assertTrue(Schema::hasColumn('sms_logs', 'existing_column'));
        $this->assertFalse(Schema::hasColumn('sms_logs', 'driver'));
    }
}

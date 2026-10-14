<?php

namespace StlHorizon\Sms\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InstallSmsCommand extends Command
{
    protected $signature = 'sms:install';

    protected $description = 'Publish SMS configuration and optionally create the package SMS log table';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'sms-config']);
        $this->info('SMS sending does not require a migration. Existing application SMS tables can be kept.');

        if (! $this->confirm('Create the package SMS log table on the default database?', false)) {
            $this->info('Migration skipped. No SMS tables were changed.');

            return self::SUCCESS;
        }

        $table = config('sms.logging.table', 'sms_logs');
        if (Schema::hasTable($table)) {
            $this->info('The configured SMS log table already exists. Migration skipped.');

            return self::SUCCESS;
        }

        return $this->call('migrate', [
            '--path' => realpath(__DIR__.'/../../database/migrations'),
            '--realpath' => true,
        ]);
    }
}

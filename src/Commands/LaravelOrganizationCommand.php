<?php

namespace CleaniqueCoders\LaravelOrganization\Commands;

use Illuminate\Console\Command;

class LaravelOrganizationCommand extends Command
{
    public $signature = 'laravel-organization';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}

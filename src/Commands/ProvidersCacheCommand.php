<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;

/**
 * @deprecated Will be removed in future release.
 */
class ProvidersCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:providers:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache the provider configs for messenger';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->info('This command is deprecated.');
    }
}

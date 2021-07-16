<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;

/**
 * @deprecated Will be removed in future release.
 */
class ProvidersClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:providers:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the cached providers file for messenger';

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

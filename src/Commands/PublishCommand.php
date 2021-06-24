<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:publish {--force : Overwrites all resources}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all of the main messenger config!';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->comment('Publishing Messenger Configuration...');

        $this->callSilent('vendor:publish', [
            '--tag' => 'messenger.config',
            '--force' => $this->option('force'),
        ]);

        $this->info('Messenger config published successfully!');
    }
}

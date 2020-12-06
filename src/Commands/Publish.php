<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;

class Publish extends Command
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
    protected $description = 'Publish all of the main messenger resources!';

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
            '--force' => $this->option('force')
        ]);

        $this->comment('Publishing Messenger Assets...');

        $this->callSilent('vendor:publish', [
            '--tag' => 'messenger.assets',
            '--force' => $this->option('force')
        ]);

        $this->comment('Publishing Messenger Views...');

        $this->callSilent('vendor:publish', [
            '--tag' => 'messenger.views',
            '--force' => $this->option('force')
        ]);

        $this->info('Messenger files published successfully!');
    }
}
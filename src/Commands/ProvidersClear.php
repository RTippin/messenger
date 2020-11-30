<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ProvidersClear extends Command
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
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new config cache command instance.
     *
     * @param Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->files->delete($this->laravel->bootstrapPath('cache/messenger.php'));

        $this->info('Messenger Providers cache cleared!');
    }
}

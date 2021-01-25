<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;
use LogicException;
use RTippin\Messenger\Services\ProvidersVerification;
use Throwable;

class ProvidersCache extends Command
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
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * @var ProvidersVerification
     */
    private ProvidersVerification $providersVerification;

    /**
     * Create a new config cache command instance.
     *
     * @param Filesystem $files
     * @param ProvidersVerification $providersVerification
     */
    public function __construct(Filesystem $files, ProvidersVerification $providersVerification)
    {
        parent::__construct();

        $this->files = $files;
        $this->providersVerification = $providersVerification;
    }

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws LogicException
     * @noinspection PhpIncludeInspection
     */
    public function handle()
    {
        $this->call('messenger:providers:clear');

        $config = $this->getFreshConfiguration();

        $configPath = $this->laravel->bootstrapPath('cache/messenger.php');

        $this->files->put(
            $configPath, '<?php return '.var_export($config, true).';'.PHP_EOL
        );

        try {
            require $configPath;
        } catch (Throwable $e) {
            $this->files->delete($configPath);

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }

        $this->info('Messenger Providers cached successfully!');
    }

    /**
     * Boot a fresh copy of the application configuration.
     *
     * @return array
     * @noinspection PhpIncludeInspection
     */
    protected function getFreshConfiguration(): array
    {
        $app = require $this->laravel->bootstrapPath().'/app.php';

        $app->useStoragePath($this->laravel->storagePath());

        $app->make(ConsoleKernelContract::class)->bootstrap();

        return $this->providersVerification->formatValidProviders(
            $app['config']['messenger']['providers']
        )->toArray();
    }
}

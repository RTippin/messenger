<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RTippin\Messenger\Messenger;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:install 
                                      {--uuids : Use UUIDs for your providers} 
                                      {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Messenger resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! $this->confirm('Really install Messenger?')) {
            return;
        }

        $this->newLine();
        $this->info('Publishing Messenger Configuration...');
        $this->newLine();

        $this->call('vendor:publish', [
            '--tag' => 'messenger.config',
            '--force' => $this->option('force'),
        ]);

        $this->newLine();
        $this->info('Publishing Messenger Service Provider...');
        $this->newLine();

        $this->call('vendor:publish', [
            '--tag' => 'messenger.provider',
            '--force' => $this->option('force'),
        ]);

        $this->registerMessengerServiceProvider();

        if ($this->option('uuids')) {
            $this->newLine();
            $this->info('Enabling UUIDs...');
            $this->newLine();

            $this->configureUuids();
        }

        if ($this->confirm('Would you like to migrate now?')) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('Messenger scaffolding successfully installed!');
    }

    /**
     * Register the Messenger service provider in the application configuration file.
     */
    private function registerMessengerServiceProvider(): void
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\MessengerServiceProvider::class')) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".$eol,
            "{$namespace}\\Providers\EventServiceProvider::class,".$eol."        {$namespace}\Providers\MessengerServiceProvider::class,".$eol,
            $appConfig
        ));

        file_put_contents(app_path('Providers/MessengerServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/MessengerServiceProvider.php'))
        ));
    }

    /**
     * Configure our Provider UUIDs to TRUE.
     */
    private function configureUuids(): void
    {
        config(['messenger.provider_uuids' => true]);
        Messenger::shouldUseUuids(true);

        file_put_contents(
            config_path('messenger.php'),
            str_replace(
                '\'provider_uuids\' => false',
                '\'provider_uuids\' => true',
                file_get_contents(config_path('messenger.php'))
            )
        );
    }
}

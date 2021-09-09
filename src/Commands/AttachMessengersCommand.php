<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger as MessengerFacade;
use RTippin\Messenger\Models\Messenger;

class AttachMessengersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:attach:messengers 
                                      {--provider= : Only attach messengers for the given provider. eg: --provider="App\Models\User"} 
                                      {--force : Attach messengers without checking if one exist for each provider.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Attach our Messenger model to your existing providers';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! $this->confirm('Really attach messenger models?')) {
            return;
        }

        if (! is_null($this->option('provider'))) {
            if (! MessengerFacade::isValidMessengerProvider($this->option('provider'))) {
                $this->error($this->option('provider').' is not a valid messenger provider.');

                return;
            }

            $this->attachForProvider($this->option('provider'));
            $this->info('Finished attaching.');

            return;
        }

        foreach (MessengerFacade::getAllProviders(true) as $provider) {
            $this->attachForProvider($provider);
        }

        $this->info('Finished attaching.');
    }

    /**
     * @param  string  $provider
     */
    private function attachForProvider(string $provider): void
    {
        $this->info("Attaching messenger's to $provider.");

        /** @var MessengerProvider $provider */
        $provider::query()->chunk(100, function (Collection $models) {
            $this->option('force')
                ? $this->forceCreateMessenger($models)
                : $this->firstOrCreateMessenger($models);
        });

        $this->info("Completed $provider.");
    }

    /**
     * @param  Collection  $models
     */
    private function firstOrCreateMessenger(Collection $models): void
    {
        $models->each(fn (MessengerProvider $provider) => MessengerFacade::getProviderMessenger($provider));
    }

    /**
     * @param  Collection  $models
     */
    private function forceCreateMessenger(Collection $models): void
    {
        $models->each(fn (MessengerProvider $provider) => Messenger::factory()->owner($provider)->create());
    }
}

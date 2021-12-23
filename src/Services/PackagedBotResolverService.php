<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\DataTransferObjects\PackagedBotInstallDTO;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Models\Thread;

class PackagedBotResolverService
{
    /**
     * @var BotHandlerResolverService
     */
    private BotHandlerResolverService $resolver;

    /**
     * @var Collection
     */
    private Collection $resolved;

    /**
     * @var Collection
     */
    private Collection $failed;

    /**
     * @var bool
     */
    private bool $logFailures;

    /**
     * @param  BotHandlerResolverService  $resolver
     */
    public function __construct(BotHandlerResolverService $resolver)
    {
        $this->resolver = $resolver;
        $this->resolved = Collection::make();
        $this->failed = Collection::make();
        $this->logFailures = false;
    }

    /**
     * Transform a packaged bots install array into a collection of
     * ResolvedBotHandlerDTO's. Filter only authorized handlers, and
     * remove any handlers flagged as unique that are already present
     * in the thread. Handlers failing validation will be ignored.
     *
     * @param  Thread  $thread
     * @param  PackagedBotDTO  $package
     * @return Collection
     */
    public function resolve(Thread $thread, PackagedBotDTO $package): Collection
    {
        $package->applyInstallFilters($thread);

        $package->canInstall->each(
            fn (PackagedBotInstallDTO $install) => $this->resolveHandlers($install)
        );

        return $this->resolved;
    }

    /**
     * Transform a packaged bots install array into a collection of
     * ResolvedBotHandlerDTO's without filtering or authorizing.
     * Returns resolved handlers as well as failing handlers
     * and their validation errors.
     *
     * @param  PackagedBotDTO  $package
     * @return array
     */
    public function resolveForTesting(PackagedBotDTO $package): array
    {
        $this->logFailures = true;

        $package->installs->each(
            fn (PackagedBotInstallDTO $install) => $this->resolveHandlers($install)
        );

        return [
            'resolved' => $this->resolved,
            'failed' => $this->failed,
        ];
    }

    /**
     * Loop through the individual install data collection
     * and attempt to resolve into a ResolvedBotHandlerDTO.
     *
     * @param  PackagedBotInstallDTO  $install
     * @return void
     */
    private function resolveHandlers(PackagedBotInstallDTO $install): void
    {
        $install->data->each(
            fn (array $data) => $this->resolveOrDiscardHandler(
                $data,
                $install->handler->class
            )
        );
    }

    /**
     * @param  array  $data
     * @param  string  $handler
     * @return void
     */
    private function resolveOrDiscardHandler(array $data, string $handler): void
    {
        try {
            $this->resolved->push(
                $this->resolver->resolve($data, $handler)
            );
        } catch (ValidationException $e) {
            if ($this->logFailures) {
                $this->failed->push([
                    'handler' => $handler,
                    'data' => $data,
                    'errors' => $e->errors(),
                ]);
            }
        } catch (BotException $e) {
            //Ignore.
        }
    }
}

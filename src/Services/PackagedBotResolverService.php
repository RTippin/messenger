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
     * @param  BotHandlerResolverService  $resolver
     */
    public function __construct(BotHandlerResolverService $resolver)
    {
        $this->resolver = $resolver;
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
        $resolved = Collection::make();

        $package->applyInstallFilters($thread);

        $package->canInstall->each(
            fn (PackagedBotInstallDTO $install) => $this->resolveHandlers($install, $resolved)
        );

        return $resolved;
    }

    /**
     * Loop through the individual install data collection
     * and attempt to resolve into a ResolvedBotHandlerDTO.
     *
     * @param  PackagedBotInstallDTO  $install
     * @param  Collection  $resolved
     * @return void
     */
    private function resolveHandlers(PackagedBotInstallDTO $install, Collection $resolved): void
    {
        $install->data->each(
            fn (array $data) => $this->resolveOrDiscardHandler(
                $data,
                $install->handler->class,
                $resolved
            )
        );
    }

    /**
     * @param  array  $data
     * @param  string  $handler
     * @param  Collection  $resolved
     * @return void
     */
    private function resolveOrDiscardHandler(array $data, string $handler, Collection $resolved): void
    {
        try {
            $resolved->push(
                $this->resolver->resolve($data, $handler)
            );
        } catch (ValidationException|BotException $e) {
            //Discard.
        }
    }
}

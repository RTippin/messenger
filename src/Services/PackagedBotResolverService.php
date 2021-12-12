<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\DataTransferObjects\PackagedBotInstallDTO;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;

class PackagedBotResolverService
{
    /**
     * @var MessengerBots
     */
    private MessengerBots $bots;

    /**
     * @var BotHandlerResolverService
     */
    private BotHandlerResolverService $resolver;

    /**
     * @param  MessengerBots  $bots
     * @param  BotHandlerResolverService  $resolver
     */
    public function __construct(MessengerBots $bots, BotHandlerResolverService $resolver)
    {
        $this->bots = $bots;
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
        $resolved = new Collection;

        $unique = $this->getThreadUniqueHandlers($thread);

        $authorized = $this->bots->getAuthorizedHandlers();

        $package->installs
            ->filter(fn (PackagedBotInstallDTO $install) => $authorized->contains($install->handler))
            ->reject(fn (PackagedBotInstallDTO $install) => in_array($install->handler->class, $unique))
            ->each(fn (PackagedBotInstallDTO $install) => $this->resolveHandlers($install, $resolved));

        return $resolved;
    }

    /**
     * @param  Thread  $thread
     * @return array
     */
    private function getThreadUniqueHandlers(Thread $thread): array
    {
        return BotAction::uniqueFromThread($thread->id)
            ->select(['handler'])
            ->get()
            ->transform(fn (BotAction $action) => $action->handler)
            ->toArray();
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

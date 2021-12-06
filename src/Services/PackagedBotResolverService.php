<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\DataTransferObjects\PackagedBotInstallDTO;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;

class PackagedBotResolverService
{
    /**
     * @var BotHandlerResolverService
     */
    private BotHandlerResolverService $resolver;

    /**
     * @var Collection|ResolvedBotHandlerDTO[]
     */
    private Collection $resolvedHandlers;

    /**
     * @param  BotHandlerResolverService  $resolver
     */
    public function __construct(BotHandlerResolverService $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Transform a packaged bots install array into a collection of
     * ResolvedBotHandlerDTO's that may be used to attach each
     * handler to the package's bot.
     *
     * @param  Thread  $thread
     * @param  PackagedBotDTO  $package
     * @return Collection
     */
    public function resolve(Thread $thread, PackagedBotDTO $package): Collection
    {
        $this->resolvedHandlers = new Collection;

        $uniqueFromThread = $this->getThreadUniqueHandlers($thread);

        $filtered = $this->rejectExistingUniqueHandlers($uniqueFromThread, $package);

        $filtered->each(
            fn (PackagedBotInstallDTO $install) => $this->resolveHandlers($install)
        );

        return $this->resolvedHandlers;
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
            ->toArray();
    }

    /**
     * Remove any defined bot handler flagged as unique
     * that is already present in the thread.
     *
     * @param  array  $unique
     * @param  PackagedBotDTO  $package
     * @return Collection|PackagedBotInstallDTO
     */
    private function rejectExistingUniqueHandlers(array $unique, PackagedBotDTO $package): Collection
    {
        return $package->installs->reject(
            fn (PackagedBotInstallDTO $install) => in_array($install->handler->class, $unique)
        );
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
            fn (array $data) => $this->resolveOrDiscardHandler($data, $install->handler->class)
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
            $this->resolvedHandlers->push(
                $this->resolver->resolve($data, $handler)
            );
        } catch (ValidationException|BotException $e) {
            //Discard.
        }
    }
}

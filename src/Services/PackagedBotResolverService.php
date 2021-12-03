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
        $this->resolvedHandlers = new Collection;
    }

    /**
     * @param  Thread  $thread
     * @param  PackagedBotDTO  $package
     * @return Collection
     */
    public function resolve(Thread $thread, PackagedBotDTO $package): Collection
    {
        $filtered = $this->rejectExistingUniqueHandlers($thread, $package);

        $filtered->each(
            fn (PackagedBotInstallDTO $install) => $this->resolveHandlers($install)
        );

        return $this->resolvedHandlers;
    }

    /**
     * @param  Thread  $thread
     * @param  PackagedBotDTO  $package
     * @return Collection
     */
    private function rejectExistingUniqueHandlers(Thread $thread, PackagedBotDTO $package): Collection
    {
        $unique = BotAction::uniqueFromThread($thread->id)
            ->select(['handler'])
            ->get()
            ->toArray();

        return $package->installs->reject(
            fn (PackagedBotInstallDTO $install) => in_array($install->handler->class, $unique)
        );
    }

    /**
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
    private function resolveOrDiscardHandler(array $data, string $handler)
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

<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Collection;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\Models\BotAction;
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
     * @param  Thread  $thread
     * @param  PackagedBotDTO  $package
     * @return Collection
     */
    public function resolve(Thread $thread, PackagedBotDTO $package): Collection
    {
        $filtered = $this->rejectExistingUniqueHandlers($thread, $package);

        return $package->installs;
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
            fn (array $install) => in_array($install['handler']->class, $unique)
        );
        
    }
}

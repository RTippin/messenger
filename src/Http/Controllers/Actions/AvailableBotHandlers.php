<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;

class AvailableBotHandlers
{
    use AuthorizesRequests;

    /**
     * @var MessengerBots
     */
    private MessengerBots $bots;

    /**
     * @param  MessengerBots  $bots
     */
    public function __construct(MessengerBots $bots)
    {
        $this->bots = $bots;
    }

    /**
     * Get all authorized handlers the current provider is allowed to
     * choose from, rejecting any handlers that are marked as unique
     * and have already attached inside the thread.
     *
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return Collection
     *
     * @throws AuthorizationException
     */
    public function __invoke(Thread $thread, Bot $bot): Collection
    {
        $this->authorize('create', [
            BotAction::class,
            $thread,
            $bot,
        ]);

        $unique = BotAction::getUniqueHandlersInThread($thread);

        return $this->bots->getAuthorizedHandlers()
            ->reject(fn (BotActionHandlerDTO $handler) => in_array($handler->class, $unique))
            ->values();
    }
}

<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Http\Collections\BotHandlerCollection;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;

class AvailableBotHandlers
{
    use AuthorizesRequests;

    /**
     * @param MessengerBots $bots
     * @param Thread $thread
     * @param Bot $bot
     * @return BotHandlerCollection
     * @throws AuthorizationException
     */
    public function __invoke(MessengerBots $bots,
                             Thread $thread,
                             Bot $bot): BotHandlerCollection
    {
        $this->authorize('create', [
            Bot::class,
            $thread,
        ]);

        return new BotHandlerCollection($bots->getAuthorizedHandlers());
    }
}

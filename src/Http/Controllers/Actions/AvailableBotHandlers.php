<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
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
     * and have already attached to the bot provided.
     *
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return array
     *
     * @throws AuthorizationException
     */
    public function __invoke(Thread $thread, Bot $bot): array
    {
        $this->authorize('create', [
            BotAction::class,
            $thread,
            $bot,
        ]);

        return $this->generateAvailableHandlers($bot);
    }

    /**
     * @param  Bot  $bot
     * @return array
     */
    private function generateAvailableHandlers(Bot $bot): array
    {
        $unique = $bot->validUniqueActions()
            ->get()
            ->transform(fn (BotAction $action) => $action->getHandlerSettings()['alias'] ?? null)
            ->filter()
            ->toArray();

        return (new Collection($this->bots->getAuthorizedHandlers()))
            ->reject(fn ($handler) => in_array($handler['alias'], $unique))
            ->values()
            ->toArray();
    }
}

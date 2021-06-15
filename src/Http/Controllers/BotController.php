<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Bots\ArchiveBot;
use RTippin\Messenger\Actions\Bots\StoreBot;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Request\BotRequest;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;

class BotController
{
    use AuthorizesRequests;

    /**
     * Display a listing of thread bots.
     *
     * @param Thread $thread
     * @throws AuthorizationException
     */
    public function index(Thread $thread)
    {
        $this->authorize('viewAny', [
            Bot::class,
            $thread,
        ]);

        return $thread->bots;
    }

    /**
     * Display the bot.
     *
     * @param Thread $thread
     * @param Bot $bot
     * @return Bot
     * @throws AuthorizationException
     */
    public function show(Thread $thread, Bot $bot): Bot
    {
        $this->authorize('view', [
            Bot::class,
            $thread,
        ]);

        return $bot;
    }

    /**
     * Store a bot.
     *
     * @param BotRequest $request
     * @param StoreBot $storeBot
     * @param Thread $thread
     * @return Bot
     * @throws AuthorizationException|FeatureDisabledException
     */
    public function store(BotRequest $request,
                          StoreBot $storeBot,
                          Thread $thread): Bot
    {
        $this->authorize('create', [
            Bot::class,
            $thread,
        ]);

        return $storeBot->execute(
            $thread,
            $request->validated()
        )->getJsonResource();
    }

    /**
     * Update the bot.
     */
    public function update()
    {
        //
    }

    /**
     * Remove the bot.
     *
     * @param ArchiveBot $archiveBot
     * @param Thread $thread
     * @param Bot $bot
     * @return JsonResponse
     * @throws AuthorizationException|FeatureDisabledException
     */
    public function destroy(ArchiveBot $archiveBot,
                            Thread $thread,
                            Bot $bot): JsonResponse
    {
        $this->authorize('delete', [
            Bot::class,
            $thread,
        ]);

        return $archiveBot->execute($bot)->getMessageResponse();
    }
}

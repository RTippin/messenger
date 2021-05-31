<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\Messenger;
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
     * @param Request $request
     * @param Thread $thread
     * @return Bot
     * @throws AuthorizationException
     */
    public function store(Request $request, Thread $thread)
    {
        $this->authorize('create', [
            Bot::class,
            $thread,
        ]);

        return $thread->bots()->create([
            'owner_id' => Messenger::getProvider()->getKey(),
            'owner_type' => Messenger::getProvider()->getMorphClass(),
            'name' => $request->input('name'),
        ]);
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
     */
    public function destroy()
    {
        //
    }
}

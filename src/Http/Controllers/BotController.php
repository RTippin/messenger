<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Models\Bot;

class BotController
{
    use AuthorizesRequests;

    /**
     * Display a listing of thread bots.
     *
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
     */
    public function show(Thread $thread, Bot $bot)
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
            'name' => $request->input('name')
        ]);
    }

    /**
     * Update the .
     *
     */
    public function update()
    {
        //
    }

    /**
     * Remove the .
     *
     */
    public function destroy()
    {
        //
    }
}

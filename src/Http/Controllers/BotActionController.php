<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RTippin\Messenger\Actions\Bots\StoreBotAction;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;

class BotActionController
{
    use AuthorizesRequests;

    /**
     * Display a listing of bot actions.
     */
    public function index(Thread $thread)
    {
        //
    }

    /**
     * Display the bot action.
     */
    public function show()
    {
        //
    }

    /**
     * @param Request $request
     * @param MessengerBots $bots
     * @param StoreBotAction $storeBotAction
     * @param Thread $thread
     * @param Bot $bot
     * @throws FeatureDisabledException|ValidationException|BotException
     */
    public function store(Request $request,
                          MessengerBots $bots,
                          StoreBotAction $storeBotAction,
                          Thread $thread,
                          Bot $bot)
    {
        $resolved = $bots->resolveHandlerData($request->all());

        return $storeBotAction->execute($bot, $resolved)->getJsonResource();
    }

    /**
     * Update the bot action.
     */
    public function update()
    {
        //
    }

    /**
     * Remove the bot action.
     */
    public function destroy()
    {
        //
    }
}

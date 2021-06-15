<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RTippin\Messenger\Actions\Bots\RemoveBotAction;
use RTippin\Messenger\Actions\Bots\StoreBotAction;
use RTippin\Messenger\Actions\Bots\UpdateBotAction;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;

class BotActionController
{
    use AuthorizesRequests;

    /**
     * Display a listing of bot actions.
     *
     * @param Thread $thread
     * @param Bot $bot
     * @throws AuthorizationException
     */
    public function index(Thread $thread, Bot $bot)
    {
        $this->authorize('viewAny', [
            Bot::class,
            $thread,
        ]);

        return $bot->actions;
    }

    /**
     * Display the bot action.
     *
     * @param Thread $thread
     * @param Bot $bot
     * @param BotAction $action
     * @return BotAction
     * @throws AuthorizationException
     */
    public function show(Thread $thread, Bot $bot, BotAction $action)
    {
        $this->authorize('view', [
            Bot::class,
            $thread,
        ]);

        return $action;
    }

    /**
     * @param Request $request
     * @param MessengerBots $bots
     * @param StoreBotAction $storeBotAction
     * @param Thread $thread
     * @param Bot $bot
     * @throws FeatureDisabledException|ValidationException
     * @throws BotException|AuthorizationException
     */
    public function store(Request $request,
                          MessengerBots $bots,
                          StoreBotAction $storeBotAction,
                          Thread $thread,
                          Bot $bot)
    {
        $this->authorize('create', [
            Bot::class,
            $thread,
        ]);

        $resolved = $bots->resolveHandlerData($request->all());

        return $storeBotAction->execute(
            $thread,
            $bot,
            $resolved
        )->getJsonResource();
    }

    /**
     * @param Request $request
     * @param MessengerBots $bots
     * @param UpdateBotAction $updateBotAction
     * @param Thread $thread
     * @param Bot $bot
     * @param BotAction $action
     * @throws FeatureDisabledException|ValidationException
     * @throws BotException|AuthorizationException
     */
    public function update(Request $request,
                           MessengerBots $bots,
                           UpdateBotAction $updateBotAction,
                           Thread $thread,
                           Bot $bot,
                           BotAction $action)
    {
        $this->authorize('update', [
            Bot::class,
            $thread,
        ]);

        $resolved = $bots->resolveHandlerData($request->all(), $action->handler);

        return $updateBotAction->execute(
            $action,
            $resolved
        )->getJsonResource();
    }

    /**
     * Remove the bot action.
     *
     * @param RemoveBotAction $removeBotAction
     * @param Thread $thread
     * @param Bot $bot
     * @param BotAction $action
     * @return JsonResponse
     * @throws AuthorizationException|FeatureDisabledException
     */
    public function destroy(RemoveBotAction $removeBotAction,
                            Thread $thread,
                            Bot $bot,
                            BotAction $action): JsonResponse
    {
        $this->authorize('delete', [
            Bot::class,
            $thread,
        ]);

        return $removeBotAction->execute($action)->getMessageResponse();
    }
}

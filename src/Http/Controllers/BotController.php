<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Bots\ArchiveBot;
use RTippin\Messenger\Actions\Bots\DestroyBotAvatar;
use RTippin\Messenger\Actions\Bots\StoreBot;
use RTippin\Messenger\Actions\Bots\StoreBotAvatar;
use RTippin\Messenger\Actions\Bots\UpdateBot;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Collections\BotCollection;
use RTippin\Messenger\Http\Request\BotAvatarRequest;
use RTippin\Messenger\Http\Request\BotRequest;
use RTippin\Messenger\Http\Resources\BotResource;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;

class BotController
{
    use AuthorizesRequests;

    /**
     * Display a listing of bots.
     *
     * @param  Thread  $thread
     * @return BotCollection
     *
     * @throws AuthorizationException
     */
    public function index(Thread $thread): BotCollection
    {
        $this->authorize('viewAny', [
            Bot::class,
            $thread,
        ]);

        return new BotCollection(
            $thread->bots()
                ->with('owner')
                ->withCount('validActions')
                ->get()
        );
    }

    /**
     * Display the bot.
     *
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return BotResource
     *
     * @throws AuthorizationException
     */
    public function show(Thread $thread, Bot $bot): BotResource
    {
        $this->authorize('view', [
            $bot,
            $thread,
        ]);

        return new BotResource(
            $bot,
            $bot->isActionsVisible($thread)
        );
    }

    /**
     * Store a bot.
     *
     * @param  BotRequest  $request
     * @param  StoreBot  $storeBot
     * @param  Thread  $thread
     * @return BotResource
     *
     * @throws AuthorizationException|FeatureDisabledException
     */
    public function store(BotRequest $request,
                          StoreBot $storeBot,
                          Thread $thread): BotResource
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
     *
     * @param  BotRequest  $request
     * @param  UpdateBot  $updateBot
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return BotResource
     *
     * @throws AuthorizationException|FeatureDisabledException
     */
    public function update(BotRequest $request,
                           UpdateBot $updateBot,
                           Thread $thread,
                           Bot $bot): BotResource
    {
        $this->authorize('update', [
            $bot,
            $thread,
        ]);

        return $updateBot->execute(
            $bot,
            $request->validated()
        )->getJsonResource();
    }

    /**
     * Store the bots avatar.
     *
     * @param  BotAvatarRequest  $request
     * @param  StoreBotAvatar  $storeBotAvatar
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return BotResource
     *
     * @throws AuthorizationException|FeatureDisabledException
     */
    public function storeAvatar(BotAvatarRequest $request,
                                StoreBotAvatar $storeBotAvatar,
                                Thread $thread,
                                Bot $bot): BotResource
    {
        $this->authorize('update', [
            $bot,
            $thread,
        ]);

        return $storeBotAvatar->execute(
            $bot,
            $request->validated()['image']
        )->getJsonResource();
    }

    /**
     * Remove the bots avatar.
     *
     * @param  DestroyBotAvatar  $destroyBotAvatar
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return BotResource
     *
     * @throws AuthorizationException|FeatureDisabledException
     */
    public function destroyAvatar(DestroyBotAvatar $destroyBotAvatar,
                                  Thread $thread,
                                  Bot $bot): BotResource
    {
        $this->authorize('update', [
            $bot,
            $thread,
        ]);

        return $destroyBotAvatar->execute($bot)->getJsonResource();
    }

    /**
     * Remove the bot.
     *
     * @param  ArchiveBot  $archiveBot
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return JsonResponse
     *
     * @throws AuthorizationException|FeatureDisabledException
     */
    public function destroy(ArchiveBot $archiveBot,
                            Thread $thread,
                            Bot $bot): JsonResponse
    {
        $this->authorize('delete', [
            $bot,
            $thread,
        ]);

        return $archiveBot->execute($bot)->getMessageResponse();
    }
}

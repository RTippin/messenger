<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;

class BotActionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the provider can view thread bots.
     *
     * @param $user
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return Response
     */
    public function viewAny($user, Thread $thread, Bot $bot): Response
    {
        return $thread->hasBotsFeature()
        && $thread->hasCurrentProvider()
        && ! $thread->isLocked()
        && $bot->isActionsVisible($thread)
            ? $this->allow()
            : $this->deny('Not authorized to view bot actions.');
    }

    /**
     * Determine whether the provider can view the bot.
     *
     * @param $user
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return Response
     */
    public function view($user, Thread $thread, Bot $bot): Response
    {
        return $thread->hasBotsFeature()
        && $thread->hasCurrentProvider()
        && ! $thread->isLocked()
        && $bot->isActionsVisible($thread)
            ? $this->allow()
            : $this->deny('Not authorized to view bot action.');
    }

    /**
     * Determine whether the provider can create a new bot.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function create($user, Thread $thread): Response
    {
        return $thread->canManageBots()
            ? $this->allow()
            : $this->deny('Not authorized add bot actions.');
    }

    /**
     * Determine whether the provider can edit the bot.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function update($user, Thread $thread): Response
    {
        return $thread->canManageBots()
            ? $this->allow()
            : $this->deny('Not authorized to update bot action.');
    }

    /**
     * Determine whether the provider can delete the bot.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function delete($user, Thread $thread): Response
    {
        return $thread->canManageBots()
            ? $this->allow()
            : $this->deny('Not authorized to remove bot action.');
    }
}

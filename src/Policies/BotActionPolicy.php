<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;

class BotActionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the provider can view bot actions.
     *
     * @param $user
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return Response
     */
    public function viewAny($user, Thread $thread, Bot $bot): Response
    {
        return $thread->id === $bot->thread_id
        && $thread->hasBotsFeature()
        && $thread->hasCurrentProvider()
        && ! $thread->isLocked()
        && $bot->isActionsVisible($thread)
            ? $this->allow()
            : $this->deny('Not authorized to view bot actions.');
    }

    /**
     * Determine whether the provider can view the bot action.
     *
     * @param $user
     * @param  BotAction  $action
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return Response
     */
    public function view($user,
                         BotAction $action,
                         Thread $thread,
                         Bot $bot): Response
    {
        return $thread->id === $bot->thread_id
        && (string) $bot->id === (string) $action->bot_id
        && $thread->hasBotsFeature()
        && $thread->hasCurrentProvider()
        && ! $thread->isLocked()
        && $bot->isActionsVisible($thread)
            ? $this->allow()
            : $this->deny('Not authorized to view bot action.');
    }

    /**
     * Determine whether the provider can create a new bot action.
     *
     * @param $user
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return Response
     */
    public function create($user, Thread $thread, Bot $bot): Response
    {
        return $thread->id === $bot->thread_id
        && $thread->canManageBots()
            ? $this->allow()
            : $this->deny('Not authorized add bot actions.');
    }

    /**
     * Determine whether the provider can edit the bot action.
     *
     * @param $user
     * @param  BotAction  $action
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return Response
     */
    public function update($user,
                           BotAction $action,
                           Thread $thread,
                           Bot $bot): Response
    {
        return $thread->id === $bot->thread_id
        && (string) $bot->id === (string) $action->bot_id
        && $thread->canManageBots()
            ? $this->allow()
            : $this->deny('Not authorized to update bot action.');
    }

    /**
     * Determine whether the provider can delete the bot action.
     *
     * @param $user
     * @param  BotAction  $action
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @return Response
     */
    public function delete($user,
                           BotAction $action,
                           Thread $thread,
                           Bot $bot): Response
    {
        return $thread->id === $bot->thread_id
        && (string) $bot->id === (string) $action->bot_id
        && $thread->canManageBots()
            ? $this->allow()
            : $this->deny('Not authorized to remove bot action.');
    }
}

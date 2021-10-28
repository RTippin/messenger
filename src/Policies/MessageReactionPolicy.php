<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;

class MessageReactionPolicy
{
    use HandlesAuthorization;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * MessageReactionPolicy constructor.
     *
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Determine whether the provider can view message reactions.
     *
     * @param $user
     * @param  Message  $message
     * @param  Thread  $thread
     * @return Response
     */
    public function viewAny($user, Message $message, Thread $thread): Response
    {
        return $thread->id === $message->thread_id
        && $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view message reactions.');
    }

    /**
     * Determine whether the provider can create a message reaction.
     *
     * @param $user
     * @param  Message  $message
     * @param  Thread  $thread
     * @return Response
     */
    public function create($user, Thread $thread, Message $message): Response
    {
        return $thread->id === $message->thread_id
        && $message->notSystemMessage()
        && ! $thread->isLocked()
        && ! $thread->isAwaitingMyApproval()
            ? $this->allow()
            : $this->deny('Not authorized to react to that message.');
    }

    /**
     * Determine whether the provider can delete the message reaction.
     *
     * @param $user
     * @param  MessageReaction  $reaction
     * @param  Thread  $thread
     * @param  Message  $message
     * @return Response
     */
    public function delete($user,
                           MessageReaction $reaction,
                           Thread $thread,
                           Message $message): Response
    {
        return $thread->id === $message->thread_id
        && $message->id === $reaction->message_id
        && ! $thread->isLocked()
        && ($reaction->isOwnedByCurrentProvider()
            || $thread->isAdmin())
            ? $this->allow()
            : $this->deny('Not authorized to remove reaction.');
    }
}

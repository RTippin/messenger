<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Actions\Messages\RemoveReaction;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\ReactionException;
use RTippin\Messenger\Http\Collections\MessageReactionCollection;
use RTippin\Messenger\Http\Request\MessageReactionRequest;
use RTippin\Messenger\Http\Resources\MessageReactionResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use Throwable;

class MessageReactionController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the message reactions.
     *
     * @param  Thread  $thread
     * @param  Message  $message
     * @return MessageReactionCollection
     *
     * @throws AuthorizationException
     */
    public function index(Thread $thread, Message $message): MessageReactionCollection
    {
        $this->authorize('viewAny', [
            MessageReaction::class,
            $message,
            $thread,
        ]);

        return new MessageReactionCollection(
            $message->reactions()->with('owner')->get()
        );
    }

    /**
     * Store a new reaction for the given message.
     *
     * @param  MessageReactionRequest  $request
     * @param  AddReaction  $addReaction
     * @param  Thread  $thread
     * @param  Message  $message
     * @return MessageReactionResource
     *
     * @throws Throwable|ReactionException
     * @throws FeatureDisabledException|AuthorizationException
     */
    public function store(MessageReactionRequest $request,
                          AddReaction $addReaction,
                          Thread $thread,
                          Message $message): MessageReactionResource
    {
        $this->authorize('create', [
            MessageReaction::class,
            $thread,
            $message,
        ]);

        return $addReaction->execute(
            $thread,
            $message,
            $request->validated()['reaction']
        )->getJsonResource();
    }

    /**
     * Remove the specified reaction from the given message.
     *
     * @param  RemoveReaction  $removeReaction
     * @param  Thread  $thread
     * @param  Message  $message
     * @param  MessageReaction  $reaction
     * @return JsonResponse
     *
     * @throws AuthorizationException|Throwable
     */
    public function destroy(RemoveReaction $removeReaction,
                            Thread $thread,
                            Message $message,
                            MessageReaction $reaction): JsonResponse
    {
        $this->authorize('delete', [
            MessageReaction::class,
            $reaction,
            $thread,
            $message,
        ]);

        return $removeReaction->execute(
            $thread,
            $message,
            $reaction
        )->getMessageResponse();
    }
}

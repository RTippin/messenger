<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\ReactionException;
use RTippin\Messenger\Http\Request\MessageReactionRequest;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use Throwable;

class MessageReactionController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the message reactions.
     */
    public function index()
    {
        //TODO
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param MessageReactionRequest $request
     * @param AddReaction $addReaction
     * @param Thread $thread
     * @param Message $message
     * @return JsonResponse
     * @throws Throwable|ReactionException|FeatureDisabledException|AuthorizationException
     */
    public function store(MessageReactionRequest $request,
                          AddReaction $addReaction,
                          Thread $thread,
                          Message $message)
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
        )->getMessageResponse();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        //TODO
    }
}

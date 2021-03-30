<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Http\Request\MessageReactionRequest;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;

class MessageReactionController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the message reactions.
     *
     */
    public function index()
    {
        //TODO
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param MessageReactionRequest $request
     * @param Thread $thread
     * @param Message $message
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(MessageReactionRequest $request,
                          Thread $thread,
                          Message $message)
    {
        $this->authorize('create', [
            MessageReaction::class,
            $thread,
            $message,
        ]);

        return new JsonResponse(['test' => $request->validated()]);
    }

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy()
    {
        //TODO
    }
}

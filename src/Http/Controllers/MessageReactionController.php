<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Http\Request\MessageReactionRequest;

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
     * @param MessageReactionRequest $request
     */
    public function store(MessageReactionRequest $request)
    {
        //TODO
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

<?php

namespace RTippin\Messenger\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Messages\ArchiveMessage;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Http\Collections\MessageCollection;
use RTippin\Messenger\Http\Request\MessageRequest;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\MessageRepository;
use Throwable;

class MessageController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the most recent messages.
     *
     * @param MessageRepository $repository
     * @param Thread $thread
     * @return MessageCollection
     * @throws AuthorizationException
     */
    public function index(MessageRepository $repository, Thread $thread)
    {
        $this->authorize('viewAny', [
            Message::class,
            $thread,
        ]);

        return new MessageCollection(
            $repository->getThreadMessagesIndex($thread),
            $thread->load('participants.owner')
        );
    }

    /**
     * Display message pagination.
     *
     * @param MessageRepository $repository
     * @param Thread $thread
     * @param Message $message
     * @return MessageCollection
     * @throws AuthorizationException
     */
    public function paginate(MessageRepository $repository,
                            Thread $thread,
                            Message $message)
    {
        $this->authorize('viewAny', [
            Message::class,
            $thread,
        ]);

        return new MessageCollection(
            $repository->getThreadMessagesPage($thread, $message),
            $thread,
            true,
            $message->id
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param MessageRequest $request
     * @param StoreMessage $storeMessage
     * @param Thread $thread
     * @return MessageResource
     * @throws AuthorizationException|Throwable
     */
    public function store(MessageRequest $request,
                          StoreMessage $storeMessage,
                          Thread $thread)
    {
        $this->authorize('create', [
            Message::class,
            $thread,
        ]);

        return $storeMessage->execute(
            $thread,
            $request->input('message'),
            $request->input('temporary_id')
        )->getJsonResource();
    }

    /**
     * Display the specified resource.
     *
     * @param Thread $thread
     * @param Message $message
     * @return MessageResource
     * @throws AuthorizationException
     */
    public function show(Thread $thread, Message $message)
    {
        $this->authorize('view', [
            Message::class,
            $thread,
        ]);

        return new MessageResource($message, $thread);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ArchiveMessage $archiveMessage
     * @param Thread $thread
     * @param Message $message
     * @return JsonResponse
     * @throws AuthorizationException|Exception
     */
    public function destroy(ArchiveMessage $archiveMessage,
                            Thread $thread,
                            Message $message)
    {
        $this->authorize('delete', [
            $message,
            $thread,
        ]);

        return $archiveMessage->execute(
            $thread,
            $message
        )->getMessageResponse();
    }
}

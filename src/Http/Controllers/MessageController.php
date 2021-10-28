<?php

namespace RTippin\Messenger\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RTippin\Messenger\Actions\Messages\ArchiveMessage;
use RTippin\Messenger\Actions\Messages\EditMessage;
use RTippin\Messenger\Actions\Messages\RemoveEmbeds;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Http\Collections\MessageCollection;
use RTippin\Messenger\Http\Collections\MessageEditCollection;
use RTippin\Messenger\Http\Request\EditMessageRequest;
use RTippin\Messenger\Http\Request\MessageRequest;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\MessageRepository;
use Throwable;

class MessageController extends Controller
{
    use AuthorizesRequests;

    /**
     * MessageController constructor.
     */
    public function __construct()
    {
        $this->middleware('throttle:messenger-message')->only('store');
    }

    /**
     * Display a listing of the most recent messages.
     *
     * @param  MessageRepository  $repository
     * @param  Thread  $thread
     * @return MessageCollection
     *
     * @throws AuthorizationException
     */
    public function index(MessageRepository $repository, Thread $thread): MessageCollection
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
     * Display message history pagination.
     *
     * @param  MessageRepository  $repository
     * @param  Thread  $thread
     * @param  Message  $message
     * @return MessageCollection
     *
     * @throws AuthorizationException
     */
    public function paginate(MessageRepository $repository,
                             Thread $thread,
                             Message $message): MessageCollection
    {
        $this->authorize('view', [
            $message,
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
     * Store a new message.
     *
     * @param  MessageRequest  $request
     * @param  StoreMessage  $storeMessage
     * @param  Thread  $thread
     * @return MessageResource
     *
     * @throws AuthorizationException|Throwable
     */
    public function store(MessageRequest $request,
                          StoreMessage $storeMessage,
                          Thread $thread): MessageResource
    {
        $this->authorize('create', [
            Message::class,
            $thread,
        ]);

        return $storeMessage->execute(
            $thread,
            $request->validated(),
            $request->ip()
        )->getJsonResource();
    }

    /**
     * Display the message.
     *
     * @param  Thread  $thread
     * @param  Message  $message
     * @return MessageResource
     *
     * @throws AuthorizationException
     */
    public function show(Thread $thread, Message $message): MessageResource
    {
        $this->authorize('view', [
            $message,
            $thread,
        ]);

        return new MessageResource($message, $thread, true);
    }

    /**
     * Display the message edits.
     *
     * @param  Thread  $thread
     * @param  Message  $message
     * @return MessageEditCollection
     *
     * @throws AuthorizationException
     */
    public function showEdits(Thread $thread, Message $message): MessageEditCollection
    {
        $this->authorize('viewEdits', [
            $message,
            $thread,
        ]);

        return new MessageEditCollection($message->edits()->get());
    }

    /**
     * Update the message body.
     *
     * @param  EditMessageRequest  $request
     * @param  EditMessage  $editMessage
     * @param  Thread  $thread
     * @param  Message  $message
     * @return MessageResource
     *
     * @throws AuthorizationException|Throwable
     */
    public function update(EditMessageRequest $request,
                           EditMessage $editMessage,
                           Thread $thread,
                           Message $message): MessageResource
    {
        $this->authorize('update', [
            $message,
            $thread,
        ]);

        return $editMessage->execute(
            $thread,
            $message,
            $request->input('message')
        )->getJsonResource();
    }

    /**
     * Remove embeds from message.
     *
     * @param  RemoveEmbeds  $removeEmbeds
     * @param  Thread  $thread
     * @param  Message  $message
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function removeEmbeds(RemoveEmbeds $removeEmbeds,
                                 Thread $thread,
                                 Message $message): JsonResponse
    {
        $this->authorize('removeEmbeds', [
            $message,
            $thread,
        ]);

        return $removeEmbeds->execute(
            $thread,
            $message
        )->getMessageResponse();
    }

    /**
     * Remove the message.
     *
     * @param  ArchiveMessage  $archiveMessage
     * @param  Thread  $thread
     * @param  Message  $message
     * @return JsonResponse
     *
     * @throws AuthorizationException|Exception
     */
    public function destroy(ArchiveMessage $archiveMessage,
                            Thread $thread,
                            Message $message): JsonResponse
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

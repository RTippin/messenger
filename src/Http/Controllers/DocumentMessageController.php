<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use RTippin\Messenger\Actions\Messages\StoreDocumentMessage;
use RTippin\Messenger\Http\Collections\DocumentMessageCollection;
use RTippin\Messenger\Http\Request\DocumentMessageRequest;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\DocumentMessageRepository;
use Throwable;

class DocumentMessageController extends Controller
{
    use AuthorizesRequests;

    /**
     * DocumentMessageController constructor.
     */
    public function __construct()
    {
        $this->middleware('throttle:messenger-attachment')->only('store');
    }

    /**
     * Display a listing of the most recent document files.
     *
     * @param  DocumentMessageRepository  $repository
     * @param  Thread  $thread
     * @return DocumentMessageCollection
     *
     * @throws AuthorizationException
     */
    public function index(DocumentMessageRepository $repository, Thread $thread): DocumentMessageCollection
    {
        $this->authorize('viewAny', [
            Message::class,
            $thread,
        ]);

        return new DocumentMessageCollection(
            $repository->getThreadDocumentsIndex($thread),
            $thread
        );
    }

    /**
     * Display document history pagination.
     *
     * @param  DocumentMessageRepository  $repository
     * @param  Thread  $thread
     * @param  Message  $document
     * @return DocumentMessageCollection
     *
     * @throws AuthorizationException
     */
    public function paginate(DocumentMessageRepository $repository,
                             Thread $thread,
                             Message $document): DocumentMessageCollection
    {
        $this->authorize('view', [
            $document,
            $thread,
        ]);

        return new DocumentMessageCollection(
            $repository->getThreadDocumentsPage($thread, $document),
            $thread,
            true,
            $document->id
        );
    }

    /**
     * Upload a new document file message.
     *
     * @param  DocumentMessageRequest  $request
     * @param  StoreDocumentMessage  $storeDocumentMessage
     * @param  Thread  $thread
     * @return MessageResource
     *
     * @throws AuthorizationException|Throwable
     */
    public function store(DocumentMessageRequest $request,
                          StoreDocumentMessage $storeDocumentMessage,
                          Thread $thread): MessageResource
    {
        $this->authorize('createDocument', [
            Message::class,
            $thread,
        ]);

        return $storeDocumentMessage->execute(
            $thread,
            $request->validated(),
            $request->ip()
        )->getJsonResource();
    }
}

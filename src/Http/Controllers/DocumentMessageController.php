<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Messages\StoreDocumentMessage;
use RTippin\Messenger\Http\Collections\DocumentMessageCollection;
use RTippin\Messenger\Http\Request\DocumentMessageRequest;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\DocumentMessageRepository;
use Throwable;

class DocumentMessageController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the most recent documents
     *
     * @param DocumentMessageRepository $repository
     * @param Thread $thread
     * @return DocumentMessageCollection
     * @throws AuthorizationException
     */
    public function index(DocumentMessageRepository $repository, Thread $thread)
    {
        $this->authorize('viewAny', [
            Message::class,
            $thread
        ]);

        return new DocumentMessageCollection(
            $repository->getThreadDocumentsIndex($thread),
            $thread
        );
    }

    /**
     * Display document history pagination
     *
     * @param DocumentMessageRepository $repository
     * @param Thread $thread
     * @param Message $document
     * @return DocumentMessageCollection
     * @throws AuthorizationException
     */
    public function paginate(DocumentMessageRepository $repository,
                                   Thread $thread,
                                   Message $document)
    {
        $this->authorize('viewAny', [
            Message::class,
            $thread
        ]);

        return new DocumentMessageCollection(
            $repository->getThreadDocumentsPage($thread, $document),
            $thread,
            true,
            $document->id
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DocumentMessageRequest $request
     * @param StoreDocumentMessage $storeDocumentMessage
     * @param Thread $thread
     * @return MessageResource
     * @throws AuthorizationException|Throwable
     */
    public function store(DocumentMessageRequest $request,
                          StoreDocumentMessage $storeDocumentMessage,
                          Thread $thread)
    {
        $this->authorize('createDocument', [
            Message::class,
            $thread
        ]);

        return $storeDocumentMessage->execute(
            $thread,
            $request->file('document'),
            $request->input('temporary_id')
        )->getJsonResource();
    }
}

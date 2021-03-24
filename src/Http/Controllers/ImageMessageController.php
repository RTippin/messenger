<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Exceptions\UploadFailedException;
use RTippin\Messenger\Http\Collections\ImageMessageCollection;
use RTippin\Messenger\Http\Request\ImageMessageRequest;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\ImageMessageRepository;
use Throwable;

class ImageMessageController extends Controller
{
    use AuthorizesRequests;

    /**
     * ImageMessageController constructor.
     */
    public function __construct()
    {
        $this->middleware('throttle:messenger-attachment')->only('store');
    }

    /**
     * Display a listing of the most recent images.
     *
     * @param ImageMessageRepository $repository
     * @param Thread $thread
     * @return ImageMessageCollection
     * @throws AuthorizationException
     */
    public function index(ImageMessageRepository $repository,
                          Thread $thread): ImageMessageCollection
    {
        $this->authorize('viewAny', [
            Message::class,
            $thread,
        ]);

        return new ImageMessageCollection(
            $repository->getThreadImagesIndex($thread),
            $thread
        );
    }

    /**
     * Display log history pagination.
     *
     * @param ImageMessageRepository $repository
     * @param Thread $thread
     * @param Message $image
     * @return ImageMessageCollection
     * @throws AuthorizationException
     */
    public function paginate(ImageMessageRepository $repository,
                                 Thread $thread,
                                 Message $image): ImageMessageCollection
    {
        $this->authorize('viewAny', [
            Message::class,
            $thread,
        ]);

        return new ImageMessageCollection(
            $repository->getThreadImagesPage($thread, $image),
            $thread,
            true,
            $image->id
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ImageMessageRequest $request
     * @param StoreImageMessage $storeImageMessage
     * @param Thread $thread
     * @return MessageResource
     * @throws AuthorizationException|Throwable|UploadFailedException
     */
    public function store(ImageMessageRequest $request,
                          StoreImageMessage $storeImageMessage,
                          Thread $thread): MessageResource
    {
        $this->authorize('createImage', [
            Message::class,
            $thread,
        ]);

        return $storeImageMessage->execute(
            $thread,
            $request->validated()
        )->getJsonResource();
    }
}

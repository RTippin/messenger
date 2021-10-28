<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use RTippin\Messenger\Actions\Messages\StoreVideoMessage;
use RTippin\Messenger\Http\Collections\VideoMessageCollection;
use RTippin\Messenger\Http\Request\VideoMessageRequest;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\VideoMessageRepository;
use Throwable;

class VideoMessageController extends Controller
{
    use AuthorizesRequests;

    /**
     * VideoMessageController constructor.
     */
    public function __construct()
    {
        $this->middleware('throttle:messenger-attachment')->only('store');
    }

    /**
     * Display a listing of the most recent video files.
     *
     * @param  VideoMessageRepository  $repository
     * @param  Thread  $thread
     * @return VideoMessageCollection
     *
     * @throws AuthorizationException
     */
    public function index(VideoMessageRepository $repository, Thread $thread): VideoMessageCollection
    {
        $this->authorize('viewAny', [
            Message::class,
            $thread,
        ]);

        return new VideoMessageCollection(
            $repository->getThreadVideosIndex($thread),
            $thread
        );
    }

    /**
     * Display video history pagination.
     *
     * @param  VideoMessageRepository  $repository
     * @param  Thread  $thread
     * @param  Message  $video
     * @return VideoMessageCollection
     *
     * @throws AuthorizationException
     */
    public function paginate(VideoMessageRepository $repository,
                             Thread $thread,
                             Message $video): VideoMessageCollection
    {
        $this->authorize('view', [
            $video,
            $thread,
        ]);

        return new VideoMessageCollection(
            $repository->getThreadVideosPage($thread, $video),
            $thread,
            true,
            $video->id
        );
    }

    /**
     * Upload a new video message.
     *
     * @param  VideoMessageRequest  $request
     * @param  StoreVideoMessage  $storeVideoMessage
     * @param  Thread  $thread
     * @return MessageResource
     *
     * @throws AuthorizationException|Throwable
     */
    public function store(VideoMessageRequest $request,
                          StoreVideoMessage $storeVideoMessage,
                          Thread $thread): MessageResource
    {
        $this->authorize('createVideo', [
            Message::class,
            $thread,
        ]);

        return $storeVideoMessage->execute(
            $thread,
            $request->validated(),
            $request->ip()
        )->getJsonResource();
    }
}

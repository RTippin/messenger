<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use RTippin\Messenger\Actions\Messages\StoreAudioMessage;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Http\Collections\AudioMessageCollection;
use RTippin\Messenger\Http\Request\AudioMessageRequest;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\AudioMessageRepository;
use Throwable;

class AudioMessageController extends Controller
{
    use AuthorizesRequests;

    /**
     * AudioMessageController constructor.
     */
    public function __construct()
    {
        $this->middleware('throttle:messenger-attachment')->only('store');
    }

    /**
     * Display a listing of the most recent audio files.
     *
     * @param  AudioMessageRepository  $repository
     * @param  Thread  $thread
     * @return AudioMessageCollection
     *
     * @throws AuthorizationException
     */
    public function index(AudioMessageRepository $repository, Thread $thread): AudioMessageCollection
    {
        $this->authorize('viewAny', [
            Message::class,
            $thread,
        ]);

        return new AudioMessageCollection(
            $repository->getThreadAudioIndex($thread),
            $thread
        );
    }

    /**
     * Display audio history pagination.
     *
     * @param  AudioMessageRepository  $repository
     * @param  Thread  $thread
     * @param  Message  $audio
     * @return AudioMessageCollection
     *
     * @throws AuthorizationException
     */
    public function paginate(AudioMessageRepository $repository,
                             Thread $thread,
                             Message $audio): AudioMessageCollection
    {
        $this->authorize('view', [
            $audio,
            $thread,
        ]);

        return new AudioMessageCollection(
            $repository->getThreadAudioPage($thread, $audio),
            $thread,
            true,
            $audio->id
        );
    }

    /**
     * Store a new audio message.
     *
     * @param  AudioMessageRequest  $request
     * @param  StoreAudioMessage  $storeAudioMessage
     * @param  Thread  $thread
     * @return MessageResource
     *
     * @throws AuthorizationException|Throwable|FileServiceException
     */
    public function store(AudioMessageRequest $request,
                          StoreAudioMessage $storeAudioMessage,
                          Thread $thread): MessageResource
    {
        $this->authorize('createAudio', [
            Message::class,
            $thread,
        ]);

        return $storeAudioMessage->execute(
            $thread,
            $request->validated(),
            $request->ip()
        )->getJsonResource();
    }
}

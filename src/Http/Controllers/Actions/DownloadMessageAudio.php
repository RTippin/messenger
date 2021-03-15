<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Exceptions\FileNotFoundException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadMessageAudio
{
    use AuthorizesRequests;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var FilesystemManager
     */
    private FilesystemManager $filesystemManager;

    /**
     * DownloadMessageAudio constructor.
     *
     * @param Messenger $messenger
     * @param FilesystemManager $filesystemManager
     */
    public function __construct(Messenger $messenger,
                                FilesystemManager $filesystemManager)
    {
        $this->messenger = $messenger;
        $this->filesystemManager = $filesystemManager;
    }

    /**
     * Download message audio.
     *
     * @param Thread $thread
     * @param Message $message
     * @param string $audio
     * @return StreamedResponse
     * @throws FileNotFoundException|AuthorizationException
     */
    public function __invoke(Thread $thread,
                             Message $message,
                             string $audio): StreamedResponse
    {
        $this->authorize('view', [
            Message::class,
            $thread,
        ]);

        $this->checkAudioExist($message, $audio);

        return $this->filesystemManager
            ->disk($message->getStorageDisk())
            ->download($message->getAudioPath());
    }

    /**
     * @param Message $message
     * @param string $audioNameChallenge
     */
    private function checkAudioExist(Message $message, string $audioNameChallenge)
    {
        if (! $message->isAudio()
            || $audioNameChallenge !== $message->body
            || ! $this->filesystemManager
                ->disk($message->getStorageDisk())
                ->exists($message->getAudioPath())) {
            throw new FileNotFoundException($audioNameChallenge);
        }
    }
}

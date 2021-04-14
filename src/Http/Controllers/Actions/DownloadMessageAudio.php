<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use RTippin\Messenger\Exceptions\FileNotFoundException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
     * Stream message audio.
     *
     * @param Request $request
     * @param Thread $thread
     * @param Message $message
     * @param string $audio
     * @return BinaryFileResponse|StreamedResponse
     * @throws AuthorizationException
     */
    public function __invoke(Request $request,
                             Thread $thread,
                             Message $message,
                             string $audio)
    {
        $this->authorize('view', [
            Message::class,
            $thread,
        ]);

        $this->checkAudioExist($message, $audio);

        return $request->has('stream')
            ? $this->streamResponse($message)
            : $this->downloadResponse($message);
    }

    /**
     * @param Message $message
     * @return StreamedResponse
     */
    private function downloadResponse(Message $message): StreamedResponse
    {
        return $this->filesystemManager
            ->disk($message->getStorageDisk())
            ->download($message->getAudioPath());
    }

    /**
     * @param Message $message
     * @return BinaryFileResponse
     */
    private function streamResponse(Message $message): BinaryFileResponse
    {
        $response = new BinaryFileResponse(
            $this->filesystemManager
                ->disk($message->getStorageDisk())
                ->path($message->getAudioPath())
        );

        BinaryFileResponse::trustXSendfileTypeHeader();

        return $response;
    }

    /**
     * @param Message $message
     * @param string $audioNameChallenge
     * @return void
     */
    private function checkAudioExist(Message $message, string $audioNameChallenge): void
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

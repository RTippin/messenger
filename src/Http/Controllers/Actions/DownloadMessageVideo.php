<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use RTippin\Messenger\Exceptions\FileNotFoundException;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadMessageVideo
{
    /**
     * @var FilesystemManager
     */
    private FilesystemManager $filesystemManager;

    /**
     * DownloadMessageVideo constructor.
     *
     * @param  FilesystemManager  $filesystemManager
     */
    public function __construct(FilesystemManager $filesystemManager)
    {
        $this->filesystemManager = $filesystemManager;
    }

    /**
     * Stream or download message video.
     *
     * @param  Request  $request
     * @param  Thread  $thread
     * @param  Message  $message
     * @param  string  $video
     * @return BinaryFileResponse|StreamedResponse
     *
     * @throws FileNotFoundException
     */
    public function __invoke(Request $request,
                             Thread $thread,
                             Message $message,
                             string $video)
    {
        $this->bailIfVideoDoesntExist($message, $video);

        return $request->has('stream')
            ? $this->streamResponse($message)
            : $this->downloadResponse($message);
    }

    /**
     * @param  Message  $message
     * @return StreamedResponse
     */
    private function downloadResponse(Message $message): StreamedResponse
    {
        return $this->filesystemManager
            ->disk($message->getStorageDisk())
            ->download($message->getVideoPath());
    }

    /**
     * @param  Message  $message
     * @return BinaryFileResponse
     */
    private function streamResponse(Message $message): BinaryFileResponse
    {
        $response = new BinaryFileResponse(
            $this->filesystemManager
                ->disk($message->getStorageDisk())
                ->path($message->getVideoPath())
        );

        BinaryFileResponse::trustXSendfileTypeHeader();

        return $response;
    }

    /**
     * @param  Message  $message
     * @param  string  $videoNameChallenge
     * @return void
     *
     * @throws FileNotFoundException
     */
    private function bailIfVideoDoesntExist(Message $message, string $videoNameChallenge): void
    {
        if (! $message->isVideo()
            || $videoNameChallenge !== $message->body
            || ! $this->filesystemManager
                ->disk($message->getStorageDisk())
                ->exists($message->getVideoPath())) {
            throw new FileNotFoundException($videoNameChallenge);
        }
    }
}

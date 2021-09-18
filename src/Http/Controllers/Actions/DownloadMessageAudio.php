<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use RTippin\Messenger\Exceptions\FileNotFoundException;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadMessageAudio
{
    /**
     * @var FilesystemManager
     */
    private FilesystemManager $filesystemManager;

    /**
     * DownloadMessageAudio constructor.
     *
     * @param  FilesystemManager  $filesystemManager
     */
    public function __construct(FilesystemManager $filesystemManager)
    {
        $this->filesystemManager = $filesystemManager;
    }

    /**
     * Stream or download message audio.
     *
     * @param  Request  $request
     * @param  Thread  $thread
     * @param  Message  $message
     * @param  string  $audio
     * @return BinaryFileResponse|StreamedResponse
     *
     * @throws FileNotFoundException
     */
    public function __invoke(Request $request,
                             Thread $thread,
                             Message $message,
                             string $audio)
    {
        $this->bailIfAudioDoesntExist($message, $audio);

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
            ->download($message->getAudioPath());
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
                ->path($message->getAudioPath())
        );

        BinaryFileResponse::trustXSendfileTypeHeader();

        return $response;
    }

    /**
     * @param  Message  $message
     * @param  string  $audioNameChallenge
     * @return void
     *
     * @throws FileNotFoundException
     */
    private function bailIfAudioDoesntExist(Message $message, string $audioNameChallenge): void
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

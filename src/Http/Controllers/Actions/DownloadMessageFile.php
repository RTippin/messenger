<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Exceptions\FileNotFoundException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadMessageFile
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
     * DownloadMessageFile constructor.
     *
     * @param Messenger $messenger
     * @param FilesystemManager $filesystemManager
     */
    public function __construct(Messenger $messenger, FilesystemManager $filesystemManager)
    {
        $this->messenger = $messenger;
        $this->filesystemManager = $filesystemManager;
    }

    /**
     * Download message document.
     *
     * @param Thread $thread
     * @param Message $message
     * @param string $file
     * @return StreamedResponse
     * @throws FileNotFoundException
     */
    public function __invoke(Thread $thread,
                             Message $message,
                             string $file): StreamedResponse
    {
        $this->checkFileExist($message, $file);

        return $this->filesystemManager
            ->disk($message->getStorageDisk())
            ->download($message->getDocumentPath());
    }

    /**
     * @param Message $message
     * @param string $fileNameChallenge
     * @return void
     */
    private function checkFileExist(Message $message, string $fileNameChallenge): void
    {
        if (! $message->isDocument()
            || $fileNameChallenge !== $message->body
            || ! $this->filesystemManager
                ->disk($message->getStorageDisk())
                ->exists($message->getDocumentPath())) {
            throw new FileNotFoundException($fileNameChallenge);
        }
    }
}

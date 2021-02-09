<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
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
    public function __construct(Messenger $messenger,
                                FilesystemManager $filesystemManager)
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
     * @throws FeatureDisabledException|FileNotFoundException|AuthorizationException
     */
    public function __invoke(Thread $thread,
                             Message $message,
                             string $file): StreamedResponse
    {
        $this->authorize('view', [
            Message::class,
            $thread,
        ]);

        $this->checkDownloadsEnabled();

        $this->checkFileExist($message, $file);

        return $this->filesystemManager
            ->disk($message->getStorageDisk())
            ->download($message->getDocumentPath());
    }

    /**
     * @throws FeatureDisabledException
     */
    private function checkDownloadsEnabled()
    {
        if (! $this->messenger->isMessageDocumentDownloadEnabled()) {
            throw new FeatureDisabledException('Document downloads are currently disabled.');
        }
    }

    /**
     * @param Message $message
     * @param string $fileNameChallenge
     */
    private function checkFileExist(Message $message, string $fileNameChallenge)
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

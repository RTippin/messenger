<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * @throws AuthorizationException
     */
    public function __invoke(Thread $thread,
                             Message $message,
                             string $file)
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
     * @throws AuthorizationException
     */
    private function checkDownloadsEnabled()
    {
        if (! $this->messenger->isMessageDocumentDownloadEnabled()) {
            throw new AuthorizationException('Document downloads are currently disabled.');
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
            throw new NotFoundHttpException("File not found: {$fileNameChallenge}");
        }
    }
}

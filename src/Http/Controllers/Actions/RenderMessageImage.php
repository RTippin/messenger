<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\ImageRenderService;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RenderMessageImage
{
    use AuthorizesRequests;

    /**
     * Render message image.
     *
     * @param ImageRenderService $service
     * @param Thread $thread
     * @param Message $message
     * @param string $size
     * @param string $image
     * @return StreamedResponse|BinaryFileResponse
     * @throws AuthorizationException|FileNotFoundException
     */
    public function __invoke(ImageRenderService $service,
                           Thread $thread,
                           Message $message,
                           string $size,
                           string $image)
    {
        $this->authorize('view', [
            Message::class,
            $thread,
        ]);

        return $service->renderMessageImage(
            $message,
            $size,
            $image
        );
    }
}

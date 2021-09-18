<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\ImageRenderService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RenderMessageImage
{
    /**
     * Render message image.
     *
     * @param  ImageRenderService  $service
     * @param  Thread  $thread
     * @param  Message  $message
     * @param  string  $size
     * @param  string  $image
     * @return StreamedResponse|BinaryFileResponse
     *
     * @throws FileNotFoundException
     */
    public function __invoke(ImageRenderService $service,
                             Thread $thread,
                             Message $message,
                             string $size,
                             string $image)
    {
        return $service->renderMessageImage($message, $size, $image);
    }
}

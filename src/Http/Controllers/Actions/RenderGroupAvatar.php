<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\ImageRenderService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RenderGroupAvatar
{
    /**
     * Render group avatar.
     *
     * @param  ImageRenderService  $service
     * @param  Thread  $thread
     * @param  string  $size
     * @param  string  $image
     * @return StreamedResponse|BinaryFileResponse
     *
     * @throws FileNotFoundException|FileNotFoundException
     */
    public function __invoke(ImageRenderService $service,
                             Thread $thread,
                             string $size,
                             string $image)
    {
        return $service->renderGroupAvatar($thread, $size, $image);
    }
}

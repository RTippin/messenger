<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\ImageRenderService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RenderGroupAvatar
{
    use AuthorizesRequests;

    /**
     * Render group avatar.
     *
     * @param ImageRenderService $service
     * @param Thread $thread
     * @param string $size
     * @param string $image
     * @return StreamedResponse|BinaryFileResponse
     * @throws AuthorizationException|FileNotFoundException
     * @throws FileNotFoundException
     */
    public function __invoke(ImageRenderService $service,
                                 Thread $thread,
                                 string $size,
                                 string $image)
    {
        $this->authorize('groupMethod', $thread);

        return $service->renderGroupAvatar(
            $thread,
            $size,
            $image
        );
    }
}

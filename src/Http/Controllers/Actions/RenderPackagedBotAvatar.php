<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Http\Response;
use RTippin\Messenger\Services\ImageRenderService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RenderPackagedBotAvatar
{
    /**
     * Render packaged bot avatar.
     *
     * @param  ImageRenderService  $service
     * @param  string  $size
     * @param  string  $alias
     * @return BinaryFileResponse|Response
     */
    public function __invoke(ImageRenderService $service,
                             string $size,
                             string $alias)
    {
        return $service->renderPackagedBotAvatar($alias, $size);
    }
}

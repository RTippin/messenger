<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RTippin\Messenger\Services\Messenger\ImageRenderService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RenderProviderAvatar
{
    /**
     * Render message image
     *
     * @param ImageRenderService $service
     * @param string $alias
     * @param string $id
     * @param string $size
     * @param string $image
     * @return BinaryFileResponse
     * @throws FileNotFoundException
     */
    public function __invoke(ImageRenderService $service,
                           string $alias,
                           string $id,
                           string $size,
                           string $image)
    {
        return $service->renderProviderAvatar(
            $alias, $id, $size, $image
        );
    }
}
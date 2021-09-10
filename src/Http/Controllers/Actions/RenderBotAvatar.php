<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\ImageRenderService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RenderBotAvatar
{
    /**
     * Render group avatar.
     *
     * @param  ImageRenderService  $service
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @param  string  $size
     * @param  string  $image
     * @return StreamedResponse|BinaryFileResponse
     *
     * @throws FileNotFoundException
     */
    public function __invoke(ImageRenderService $service,
                             Thread $thread,
                             Bot $bot,
                             string $size,
                             string $image)
    {
        return $service->renderBotAvatar($bot, $size, $image);
    }
}

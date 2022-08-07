<?php

namespace RTippin\Messenger\MessageTypes;

use RTippin\Messenger\Models\Message;

class Video extends Base
{
    protected int $code = 4;

    protected string $verbose = 'VIDEO_MESSAGE';

    public function getResourceData(Message $message): ?array
    {
        return ['video' => $message->getVideoDownloadRoute()];
    }
}

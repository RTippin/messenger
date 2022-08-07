<?php

namespace RTippin\Messenger\MessageTypes;

use RTippin\Messenger\Models\Message;

class Audio extends Base
{
    protected int $code = 3;

    protected string $verbose = 'AUDIO_MESSAGE';

    public function getResourceData(Message $message): ?array
    {
        return ['audio' => $message->getAudioDownloadRoute()];
    }
}

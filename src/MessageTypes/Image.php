<?php

namespace RTippin\Messenger\MessageTypes;

use RTippin\Messenger\Models\Message;

class Image extends Base
{
    protected int $code = 1;

    protected string $verbose = 'IMAGE_MESSAGE';

    public function getResourceData(Message $message): ?array
    {
        return [
            'image' => [
                'sm' => $message->getImageViewRoute('sm'),
                'md' => $message->getImageViewRoute('md'),
                'lg' => $message->getImageViewRoute('lg'),
            ],
        ];
    }
}

<?php

namespace RTippin\Messenger;

use JoyPixels\Client;

class EmojiConverter
{
    /**
     * @var Client
     */
    private Client $joyPixelClient;

    /**
     * EmojiConverter constructor.
     * @param Client $joyPixelClient
     */
    public function __construct(Client $joyPixelClient)
    {
        $this->joyPixelClient = $joyPixelClient;
    }

    /**
     * @param string $message
     * @return string
     */
    public function toShort(string $message): string
    {
        return $this->joyPixelClient->toShort($message);
    }
}

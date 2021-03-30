<?php

namespace RTippin\Messenger\Support;

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

    /**
     * @param string $string
     * @return bool
     * TODO
     */
    public function verify(string $string): bool
    {
//        $test = preg_replace_callback('/'.$this->joyPixelClient->ignoredRegexp.'|'.$this->joyPixelClient->unicodeRegexp.'/ui', array($this, 'toShortCallback'), $string);

        return true;
    }
}

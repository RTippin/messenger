<?php

namespace RTippin\Messenger\Support;

use Illuminate\Support\Collection;
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
     */
    public function verifyHasEmoji(string $string): bool
    {
        return count($this->getValidEmojiShortcodes($string)) > 0;
    }

    /**
     * @param string $string
     * @return array
     */
    public function getValidEmojiShortcodes(string $string): array
    {
        preg_match_all('/:([^:]+):/', $this->toShort($string), $match);

        return (new Collection($match[0]))
            ->reject(fn (string $code) => ! $this->checkShortcodeExist($code))
            ->values()
            ->toArray();
    }

    /**
     * @param string $shortcode
     * @return bool
     */
    private function checkShortcodeExist(string $shortcode): bool
    {
        return array_key_exists($shortcode, $this->joyPixelClient->getRuleset()->getShortcodeReplace());
    }
}

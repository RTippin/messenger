<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Collection;
use JoyPixels\Client;
use RTippin\Messenger\Contracts\EmojiInterface;

class EmojiService implements EmojiInterface
{
    /**
     * @var Client
     */
    private Client $joyPixelClient;

    /**
     * Emoji constructor.
     *
     * @param  Client  $joyPixelClient
     */
    public function __construct(Client $joyPixelClient)
    {
        $this->joyPixelClient = $joyPixelClient;
    }

    /**
     * @inheritDoc
     */
    public function toShort(?string $message): string
    {
        return $this->joyPixelClient->toShort(
            $this->joyPixelClient->asciiToShortname($message)
        );
    }

    /**
     * @inheritDoc
     */
    public function verifyHasEmoji(?string $string): bool
    {
        return count($this->getValidEmojiShortcodes($string)) > 0;
    }

    /**
     * @inheritDoc
     */
    public function getValidEmojiShortcodes(?string $string): array
    {
        // Get all phrases between each instance of two colons (:emoji:)
        preg_match_all('/:([^:]+):/', $this->toShort($string), $matches);

        return Collection::make($matches[0])
            ->reject(fn (string $code) => ! $this->checkShortcodeExist($code))
            ->values()
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getFirstValidEmojiShortcode(?string $string): ?string
    {
        return $this->getValidEmojiShortcodes($string)[0] ?? null;
    }

    /**
     * @param  string|null  $shortcode
     * @return bool
     */
    private function checkShortcodeExist(?string $shortcode): bool
    {
        return array_key_exists($shortcode, $this->joyPixelClient->getRuleset()->getShortcodeReplace());
    }
}

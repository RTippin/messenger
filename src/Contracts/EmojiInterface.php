<?php

namespace RTippin\Messenger\Contracts;

interface EmojiInterface
{
    /**
     * @param string $message
     * @return string
     */
    public function toShort(string $message): string;

    /**
     * @param string $string
     * @return bool
     */
    public function verifyHasEmoji(string $string): bool;

    /**
     * @param string $string
     * @return array
     */
    public function getValidEmojiShortcodes(string $string): array;
}

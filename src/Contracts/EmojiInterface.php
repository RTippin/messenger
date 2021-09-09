<?php

namespace RTippin\Messenger\Contracts;

interface EmojiInterface
{
    /**
     * @param  string|null  $message
     * @return string
     */
    public function toShort(?string $message): string;

    /**
     * @param  string|null  $string
     * @return bool
     */
    public function verifyHasEmoji(?string $string): bool;

    /**
     * @param  string|null  $string
     * @return array
     */
    public function getValidEmojiShortcodes(?string $string): array;

    /**
     * @param  string|null  $string
     * @return string|null
     */
    public function getFirstValidEmojiShortcode(?string $string): ?string;
}

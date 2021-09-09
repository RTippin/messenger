<?php

namespace RTippin\Messenger\Rules;

use Illuminate\Contracts\Validation\Rule;
use RTippin\Messenger\Contracts\EmojiInterface;

class HasEmoji implements Rule
{
    /**
     * @var EmojiInterface
     */
    private EmojiInterface $emoji;

    /**
     * HasEmoji constructor.
     *
     * @param  EmojiInterface  $emoji
     */
    public function __construct(EmojiInterface $emoji)
    {
        $this->emoji = $emoji;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return is_string($value) && $this->emoji->verifyHasEmoji($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must contain a valid emoji.';
    }
}

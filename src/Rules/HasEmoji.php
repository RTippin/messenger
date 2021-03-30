<?php

namespace RTippin\Messenger\Rules;

use Illuminate\Contracts\Validation\Rule;
use RTippin\Messenger\Support\EmojiConverter;

class HasEmoji implements Rule
{
    /**
     * @var EmojiConverter
     */
    private EmojiConverter $converter;

    /**
     * HasEmoji constructor.
     *
     * @param EmojiConverter $converter
     */
    public function __construct(EmojiConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return $this->converter->verifyHasEmoji($value);
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

<?php

namespace RTippin\Messenger\Rules;

use Illuminate\Contracts\Validation\Rule;

class IntegerOrString implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return ! is_bool($value)
            && (filter_var($value, FILTER_VALIDATE_INT) !== false
                || is_string($value));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be an integer or string.';
    }
}

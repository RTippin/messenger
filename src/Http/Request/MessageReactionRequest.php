<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use RTippin\Messenger\Rules\HasEmoji;

class MessageReactionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @param  HasEmoji  $hasEmoji
     * @return array
     */
    public function rules(HasEmoji $hasEmoji): array
    {
        return [
            'reaction' => ['required', 'string', $hasEmoji],
        ];
    }
}

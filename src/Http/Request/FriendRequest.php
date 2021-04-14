<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use RTippin\Messenger\Rules\IntegerOrString;

class FriendRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', new IntegerOrString],
            'recipient_alias' => ['required', 'string'],
        ];
    }
}

<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class MessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'message' => 'required|string',
            'temporary_id' => 'required|string',
            'reply_to_id' => 'nullable|string',
            'extra' => 'nullable|array',
        ];
    }
}

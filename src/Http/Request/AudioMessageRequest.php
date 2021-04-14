<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use RTippin\Messenger\Facades\Messenger;

class AudioMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $limit = Messenger::getMessageAudioSizeLimit();
        $mimes = Messenger::getMessageAudioMimeTypes();

        return [
            'audio' => ['required', "max:$limit", 'file', "mimes:$mimes"],
            'temporary_id' => ['required', 'string'],
            'reply_to_id' => ['nullable', 'string'],
            'extra' => ['nullable', 'array'],
        ];
    }
}

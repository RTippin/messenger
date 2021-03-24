<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use RTippin\Messenger\Facades\Messenger;

class ImageMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $limit = Messenger::getMessageImageSizeLimit();
        $mimes = Messenger::getMessageImageMimeTypes();

        return [
            'image' => "required|max:{$limit}|file|mimes:{$mimes}",
            'temporary_id' => 'required|string',
            'reply_to_id' => 'nullable|string',
        ];
    }
}

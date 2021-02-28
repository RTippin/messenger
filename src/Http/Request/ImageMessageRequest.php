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

        return [
            'image' => "required|max:{$limit}|file|image",
            'temporary_id' => 'required|string',
        ];
    }
}

<?php

namespace RTippin\Messenger\Http\Request;

class ImageMessageRequest extends BaseMessageRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->generateRules([
            'image',
            'temporary_id',
            'reply_to_id',
            'extra',
        ]);
    }
}

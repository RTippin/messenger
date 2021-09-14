<?php

namespace RTippin\Messenger\Http\Request;

class VideoMessageRequest extends BaseMessageRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->generateRules([
            'video',
            'temporary_id',
            'reply_to_id',
            'extra',
        ]);
    }
}

<?php

namespace RTippin\Messenger\Http\Request;

class DocumentMessageRequest extends BaseMessageRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->generateRules([
            'document',
            'temporary_id',
            'reply_to_id',
            'extra',
        ]);
    }
}

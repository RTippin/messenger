<?php

namespace RTippin\Messenger\Http\Request;

class EditMessageRequest extends BaseMessageRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->generateRules([
            'message',
        ]);
    }
}

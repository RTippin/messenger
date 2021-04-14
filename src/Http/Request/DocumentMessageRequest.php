<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use RTippin\Messenger\Facades\Messenger;

class DocumentMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $limit = Messenger::getMessageDocumentSizeLimit();
        $mimes = Messenger::getMessageDocumentMimeTypes();

        return [
            'document' => ['required', "max:$limit", 'file', "mimes:$mimes"],
            'temporary_id' => ['required', 'string'],
            'reply_to_id' => ['nullable', 'string'],
            'extra' => ['nullable', 'array'],
        ];
    }
}

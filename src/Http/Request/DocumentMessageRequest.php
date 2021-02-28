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

        return [
            'document' => "required|max:{$limit}|file|mimes:pdf,doc,ppt,xls,docx,pptx,xlsx,rar,zip,7z",
            'temporary_id' => 'required|string',
        ];
    }
}

<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class DocumentMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'document' => 'required|max:10240|file|mimes:pdf,doc,ppt,xls,docx,pptx,xlsx,rar,zip,7z',
            'temporary_id' => 'required|string',
        ];
    }
}

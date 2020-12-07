<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class PrivateThreadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'message' => 'required_without_all:document,image|string',
            'document' => 'required_without_all:message,image|max:10240|file|mimes:pdf,doc,ppt,xls,docx,pptx,xlsx,rar,zip,7z',
            'image' => 'required_without_all:document,message|max:5120|file|image',
            'recipient_id' => 'required|string',
            'recipient_alias' => 'required|string'
        ];
    }
}
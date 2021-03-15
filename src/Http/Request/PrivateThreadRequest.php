<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Rules\IntegerOrString;

class PrivateThreadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'recipient_id' => ['required', new IntegerOrString],
            'recipient_alias' => 'required|string',
            'message' => 'required_without_all:document,image,audio|string',
        ];

        if (Messenger::isMessageDocumentUploadEnabled()) {
            $limit = Messenger::getMessageDocumentSizeLimit();
            $mimes = Messenger::getMessageDocumentMimeTypes();

            $rules['document'] = "required_without_all:message,image,audio|max:{$limit}|file|mimes:{$mimes}";
        }
        if (Messenger::isMessageImageUploadEnabled()) {
            $limit = Messenger::getMessageImageSizeLimit();
            $mimes = Messenger::getMessageImageMimeTypes();

            $rules['image'] = "required_without_all:document,message,audio|max:{$limit}|file|mimes:{$mimes}";
        }
        if (Messenger::isMessageAudioUploadEnabled()) {
            $limit = Messenger::getMessageAudioSizeLimit();
            $mimes = Messenger::getMessageAudioMimeTypes();

            $rules['audio'] = "required_without_all:message,image,document|max:{$limit}|file|mimes:{$mimes}";
        }

        return $rules;
    }
}

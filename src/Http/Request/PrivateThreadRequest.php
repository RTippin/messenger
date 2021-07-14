<?php

namespace RTippin\Messenger\Http\Request;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Rules\IntegerOrString;

class PrivateThreadRequest extends BaseMessageRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $messageLimit = Messenger::getMessageSizeLimit();
        $rules = [
            'recipient_id' => ['required', new IntegerOrString],
            'recipient_alias' => ['required', 'string'],
            'message' => ['required_without_all:document,image,audio', 'string', "max:$messageLimit"],
            'extra' => ['nullable', 'array'],
        ];

        if (Messenger::isMessageDocumentUploadEnabled()) {
            $docLimit = Messenger::getMessageDocumentSizeLimit();
            $docMimes = Messenger::getMessageDocumentMimeTypes();

            $rules['document'] = ['required_without_all:message,image,audio', "max:$docLimit", 'file', "mimes:$docMimes"];
        }
        if (Messenger::isMessageImageUploadEnabled()) {
            $imageLimit = Messenger::getMessageImageSizeLimit();
            $imageMimes = Messenger::getMessageImageMimeTypes();

            $rules['image'] = ['required_without_all:document,message,audio', "max:$imageLimit", 'file', "mimes:$imageMimes"];
        }
        if (Messenger::isMessageAudioUploadEnabled()) {
            $audioLimit = Messenger::getMessageAudioSizeLimit();
            $audioMimes = Messenger::getMessageAudioMimeTypes();

            $rules['audio'] = ['required_without_all:message,image,document', "max:$audioLimit", 'file', "mimes:$audioMimes"];
        }

        return $rules;
    }
}

<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Validation\Validator;
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

        return [
            'recipient_id' => ['required', new IntegerOrString],
            'recipient_alias' => ['required', 'string'],
            'message' => ['required_without_all:document,image,audio,video', 'string', "max:$messageLimit"],
            'extra' => ['nullable', 'array'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('document', [
            'required_without_all:message,image,audio,video',
            'max:'.Messenger::getMessageDocumentSizeLimit(),
            'file',
            'mimes:'.Messenger::getMessageDocumentMimeTypes(),
        ], fn () => Messenger::isMessageDocumentUploadEnabled());

        $validator->sometimes('image', [
            'required_without_all:document,message,audio,video',
            'max:'.Messenger::getMessageImageSizeLimit(),
            'file',
            'mimes:'.Messenger::getMessageImageMimeTypes(),
        ], fn () => Messenger::isMessageImageUploadEnabled());

        $validator->sometimes('audio', [
            'required_without_all:message,image,document,video',
            'max:'.Messenger::getMessageAudioSizeLimit(),
            'file',
            'mimes:'.Messenger::getMessageAudioMimeTypes(),
        ], fn () => Messenger::isMessageAudioUploadEnabled());

        $validator->sometimes('video', [
            'required_without_all:message,image,document,audio',
            'max:'.Messenger::getMessageVideoSizeLimit(),
            'file',
            'mimes:'.Messenger::getMessageVideoMimeTypes(),
        ], fn () => Messenger::isMessageVideoUploadEnabled());
    }
}

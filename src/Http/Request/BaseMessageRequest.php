<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use RTippin\Messenger\Facades\Messenger;

abstract class BaseMessageRequest extends FormRequest
{
    /**
     * @var array
     */
    private array $generatedRuleset = [];

    /**
     * If extra data is set, check if array. If not, let us
     * try to json decode and overwrite as an array.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if (! is_null($this->input('extra'))
            && ! is_array($this->input('extra'))
            && $decoded = json_decode($this->input('extra'), true)) {
            $this->merge([
                'extra' => $decoded,
            ]);
        }
    }

    /**
     * Generate the rules needed based on the attributes passed.
     *
     * @param array $fields
     * @return array
     */
    protected function generateRules(array $fields): array
    {
        if (in_array('message', $fields)) {
            $this->generateMessageRule();
        }

        if (in_array('temporary_id', $fields)) {
            $this->generateTemporaryIdRule();
        }

        if (in_array('reply_to_id', $fields)) {
            $this->generateReplyToIdRule();
        }

        if (in_array('extra', $fields)) {
            $this->generateExtraDataRule();
        }

        if (in_array('image', $fields)) {
            $this->generateImageRule();
        }

        if (in_array('audio', $fields)) {
            $this->generateAudioRule();
        }

        if (in_array('document', $fields)) {
            $this->generateDocumentRule();
        }

        return $this->generatedRuleset;
    }

    /**
     * Message ruleset.
     */
    private function generateMessageRule(): void
    {
        $limit = Messenger::getMessageSizeLimit();

        $this->generatedRuleset['message'] = ['required', 'string', "max:$limit"];
    }

    /**
     * Temporary ID ruleset.
     */
    private function generateTemporaryIdRule(): void
    {
        $this->generatedRuleset['temporary_id'] = ['required', 'string'];
    }

    /**
     * Reply to ID ruleset.
     */
    private function generateReplyToIdRule(): void
    {
        $this->generatedRuleset['reply_to_id'] = ['nullable', 'string'];
    }

    /**
     * Extra data ruleset.
     */
    private function generateExtraDataRule(): void
    {
        $this->generatedRuleset['extra'] = ['nullable', 'array'];
    }

    /**
     * Image file ruleset.
     */
    private function generateImageRule(): void
    {
        $limit = Messenger::getMessageImageSizeLimit();
        $mimes = Messenger::getMessageImageMimeTypes();

        $this->generatedRuleset['image'] = ['required', "max:$limit", 'file', "mimes:$mimes"];
    }

    /**
     * Audio file ruleset.
     */
    private function generateAudioRule(): void
    {
        $limit = Messenger::getMessageAudioSizeLimit();
        $mimes = Messenger::getMessageAudioMimeTypes();

        $this->generatedRuleset['audio'] = ['required', "max:$limit", 'file', "mimes:$mimes"];
    }

    /**
     * Document file ruleset.
     */
    private function generateDocumentRule(): void
    {
        $limit = Messenger::getMessageDocumentSizeLimit();
        $mimes = Messenger::getMessageDocumentMimeTypes();

        $this->generatedRuleset['document'] = ['required', "max:$limit", 'file', "mimes:$mimes"];
    }
}

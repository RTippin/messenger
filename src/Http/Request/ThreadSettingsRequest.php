<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ThreadSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:3'],
            'add_participants' => ['required', 'boolean'],
            'invitations' => ['required', 'boolean'],
            'calling' => ['required', 'boolean'],
            'messaging' => ['required', 'boolean'],
            'knocks' => ['required', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
//        $validator->sometimes();
    }
}

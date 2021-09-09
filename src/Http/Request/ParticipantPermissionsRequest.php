<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use RTippin\Messenger\Facades\Messenger;

class ParticipantPermissionsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'send_messages' => ['required', 'boolean'],
            'add_participants' => ['required', 'boolean'],
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
        $validator->sometimes('start_calls', [
            'required',
            'boolean',
        ], fn () => Messenger::isCallingEnabled());

        $validator->sometimes('send_knocks', [
            'required',
            'boolean',
        ], fn () => Messenger::isKnockKnockEnabled());

        $validator->sometimes('manage_invites', [
            'required',
            'boolean',
        ], fn () => Messenger::isThreadInvitesEnabled());

        $validator->sometimes('manage_bots', [
            'required',
            'boolean',
        ], fn () => Messenger::isBotsEnabled());
    }
}

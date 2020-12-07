<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

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
            'start_calls' => 'required|boolean',
            'send_knocks' => 'required|boolean',
            'send_messages' => 'required|boolean',
            'add_participants' => 'required|boolean',
            'manage_invites' => 'required|boolean'
        ];
    }
}
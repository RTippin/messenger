<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessengerSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'call_ringtone_sound' => 'required|boolean',
            'message_popups' => 'required|boolean',
            'message_sound' => 'required|boolean',
            'notify_sound' => 'required|boolean',
            'dark_mode' => 'required|boolean',
            'online_status' => ['required', 'integer', Rule::in([0,1,2])]
        ];
    }
}
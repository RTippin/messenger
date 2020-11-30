<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class ThreadSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'subject' => 'required|string|min:3',
            'add_participants' => 'required|boolean',
            'invitations' => 'required|boolean',
            'calling' => 'required|boolean',
            'messaging' => 'required|boolean',
            'knocks' => 'required|boolean',
        ];
    }
}
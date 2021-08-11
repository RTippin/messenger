<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class BotRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:255'],
            'enabled' => ['required', 'boolean'],
            'hide_actions' => ['required', 'boolean'],
            'cooldown' => ['required', 'integer', 'between:0,900'],
        ];
    }
}

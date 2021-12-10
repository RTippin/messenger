<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RTippin\Messenger\Facades\MessengerBots;

class InstallBotRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'alias' => ['required', Rule::in(MessengerBots::getPackagedBotAliases())],
        ];
    }
}

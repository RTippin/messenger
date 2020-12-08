<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class GroupThreadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'subject' => 'required|string|min:3',
            'providers' => 'nullable|array|min:1',
            'providers.*.alias' => 'required_with:providers|string',
            'providers.*.id' => 'required_with:providers|string',
        ];
    }
}

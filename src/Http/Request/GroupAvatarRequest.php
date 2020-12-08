<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RTippin\Messenger\Definitions;

class GroupAvatarRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'image' => 'required_without:default|file|max:5120|image',
            'default' => [
                'required_without:image',
                'string',
                Rule::in(Definitions::DefaultGroupAvatars),
            ],
        ];
    }
}

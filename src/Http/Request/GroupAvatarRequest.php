<?php

namespace RTippin\Messenger\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Support\Definitions;

class GroupAvatarRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $limit = Messenger::getThreadAvatarSizeLimit();
        $mimes = Messenger::getThreadAvatarMimeTypes();

        return [
            'image' => ['required_without:default', 'file', "max:$limit", "mimes:$mimes"],
            'default' => ['required_without:image', 'string', Rule::in(Definitions::DefaultGroupAvatars)],
        ];
    }
}

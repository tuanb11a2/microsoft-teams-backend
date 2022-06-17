<?php

namespace Modules\UserApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTodoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'type' => 'required|in:backlog,progress,finished,review',
            'priority' => 'required|in:high,medium,low',
            'deadline' => 'required|date',
            'groupId' => 'required|exists:groups,id'
        ];
    }
}

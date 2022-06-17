<?php

namespace Modules\UserApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTodoRequest extends FormRequest
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
            'name' => 'max:255',
            'type' => 'required|in:review,progress,finished,review',
            'priority' => 'required|in:high,medium,low',
            'groupId' => 'required|exists:groups,id',
            'deadline' => 'required|date',
        ];
    }
}

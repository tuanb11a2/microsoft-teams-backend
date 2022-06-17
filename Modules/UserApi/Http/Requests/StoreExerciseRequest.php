<?php

namespace Modules\UserApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExerciseRequest extends FormRequest
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
            'title' => 'required|max:255',
            'description' => 'max:1000',
            'deadline' => 'required|date',
            'file' => 'mimes:png,jpg,jpeg,gif,pdf,doc,docx,svg,xls,xlsx,ppt,pptx,txt,zip,rar'
        ];
    }
}

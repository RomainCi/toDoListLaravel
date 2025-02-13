<?php

namespace App\Http\Requests\Project;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'background_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:500', Rule::prohibitedIf(fn () => !empty($this->background_color)),],
            'background_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/',Rule::prohibitedIf(fn () => !empty($this->background_image))],
            'title' => 'required',
            '*' => ['required_without_all:background_color,background_image'],
        ];
    }
}

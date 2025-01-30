<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lastName' => 'required|max:30|alpha',
            'firstName' => 'required|max:30|alpha',
        ];
    }

    public function messages(): array
    {
        return [
            'lastName.required' => 'Le nom est obligatoire',
            'lastName.alpha' => 'Le nom ne doit contenir que des lettres',
            'firstName.required' => 'Le prénom est obligatoire',
            'firstName.alpha' => 'Le prénom ne doit contenir que des lettres',
        ];
    }
}

<?php

namespace App\Http\Requests\User;

use App\Rules\PasswordRule;
use Illuminate\Foundation\Http\FormRequest;


class UpdatePasswordRequest extends FormRequest
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
      return[
        'lastPassword'=> 'current_password:sanctum',
        'password' => [
            'required',
            'confirmed',
            new PasswordRule($this->isPrecognitive() ? false : true),          
        ]
        ];
    }

    /**
     * Get the validation messages.
     */
    public function messages(): array
    {
        return [
            'lastPassword.current_password'=> "Le mot de passe est incorrect",
            'required' => 'Le :attribute est obligatoire.',
            'password.confirmed' => 'La confirmation du champ du mot de passe ne correspond pas.',
        ];
    }

    /**
     * Customize the attribute names.
     */
    public function attributes(): array
    {
        return [
            'lastPassword' => 'ancien mot de passe',
            'password' => 'nouveau mot de passe',
        ];
    }
}

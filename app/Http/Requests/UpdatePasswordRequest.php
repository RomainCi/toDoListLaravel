<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

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
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'token.required' => 'Le token est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit comporter au moins :min caractères',
            'password.letters' => 'Le mot de passe doit contenir au moins une lettre',
            'password.mixed' => 'Le mot de passe doit contenir des lettres majuscules et minuscules',
            'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre',
            'password.symbols' => 'Le mot de passe doit contenir au moins un symbole',
            'password.uncompromised' => 'Le mot de passe a été compromis dans une fuite de données. Veuillez en choisir un autre.',
        ];
    }
}

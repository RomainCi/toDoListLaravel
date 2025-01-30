<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'email' => 'required|email|unique:users',
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
            'lastName.required' => 'Le nom est obligatoire',
            'lastName.alpha' => 'Le nom ne doit contenir que des lettres',
            'firstName.required' => 'Le prénom est obligatoire',
            'firstName.alpha' => 'Le prénom ne doit contenir que des lettres',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'adresse email n\'est pas valide',
            'email.unique' => 'Cet email est déjà utilisé',
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

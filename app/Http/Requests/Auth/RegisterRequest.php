<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordRule;
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
                new PasswordRule($this->isPrecognitive() ? false : true),          
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
            'password.confirmed' => 'La confirmation du champ du mot de passe ne correspond pas.',
        ];
    }
}

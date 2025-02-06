<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

class PasswordRule implements ValidationRule
{

    protected $isFinalSubmission;

    public function __construct($isFinalSubmission = false)
    {
        $this->isFinalSubmission = $isFinalSubmission;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $passwordRules = Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
        
        if ($this->isFinalSubmission) {
            $passwordRules = $passwordRules->uncompromised();
        }
        $messages = [
            'min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.letters' => 'Le mot de passe doit inclure au moins une lettre.',
            'password.mixed' => 'Le mot de passe doit inclure des majuscules et des minuscules.',
            'password.numbers' => 'Le mot de passe doit inclure au moins un chiffre.',
            'password.symbols' => 'Le mot de passe doit inclure au moins un caractère spécial.',
            'password.uncompromised' => 'Le mot de passe a été compromis dans une fuite de données. Veuillez en choisir un autre.',
        ];
        $validator = validator(
            [$attribute => $value],
            [$attribute => $passwordRules],
            $messages
        );
        
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $fail($error);
            }
        }
    }
}

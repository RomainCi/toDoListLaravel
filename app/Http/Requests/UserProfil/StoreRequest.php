<?php

namespace App\Http\Requests\UserProfil;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'picture' => 'image|mimes:jpeg,png,jpg|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'picture.image' => "Le fichier doit être une image.",
            'picture.mimes' => "L'image doit être au format JPEG, PNG ou JPG.",
            'picture.max' => "L'image ne doit pas dépasser 200 Ko.",
        ];
    }
}

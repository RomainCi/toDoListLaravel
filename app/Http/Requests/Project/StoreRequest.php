<?php

namespace App\Http\Requests\Project;

use App\Rules\BackgroundFields;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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


       /**
     * Get the validation messages.
     */
    public function messages(): array
    {
        return [
            // background_color
            'background_color.regex' => "Le code couleur doit être un code hexadécimal valide (ex: #FFF ou #FFFFFF).",
            'background_color.required_without' => "Vous devez fournir soit une couleur de fond, soit une image, mais pas les deux.",

            // background_image
            'background_image.image' => "Le fichier doit être une image.",
            'background_image.mimes' => "L'image doit être au format JPEG, PNG ou JPG.",
            'background_image.max' => "L'image ne doit pas dépasser 500 Ko.",
            'background_image.required_without' => "Vous devez fournir soit une image de fond, soit une couleur, mais pas les deux.",

            // title
            'title.required' => "Le titre est obligatoire.",
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Authorization is handled by the JWT middleware in the controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Use 'sometimes' to only validate fields if they are present in the request
        return [
            "name" => "sometimes|required|string|max:255",
            "description" => "sometimes|nullable|string",
            "price" => "sometimes|required|numeric|min:0.01", // Price must be positive
            "category_id" => "sometimes|required|integer|exists:categories,id" // Ensure category exists
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            "success" => false,
            "message" => "Validation errors",
            "data" => $validator->errors()
        ], 422)); // Use 422 Unprocessable Entity for validation errors
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        // Same messages as StoreProductRequest, adjust if needed
        return [
            "name.required" => "O nome do produto é obrigatório.",
            "price.required" => "O preço do produto é obrigatório.",
            "price.numeric" => "O preço deve ser um valor numérico.",
            "price.min" => "O preço deve ser maior que zero.",
            "category_id.required" => "A categoria é obrigatória.",
            "category_id.integer" => "O ID da categoria deve ser um número inteiro.",
            "category_id.exists" => "A categoria selecionada não existe."
        ];
    }
}


<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class AvatarUploadRequest extends FormRequest
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
            'avatar' => 'required|image|max:5120'
        ];
    }

    protected function failedValidation(Validator $validator)
{
    Log::debug("Avatar validation failed", $validator->errors()->toArray());
    throw new HttpResponseException(response()->json([
        'errors' => $validator->errors()
    ], 422));
}
}

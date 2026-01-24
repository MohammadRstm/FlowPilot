<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetPasswordRequest extends FormRequest{

    public function authorize(): bool{
        return true;
    }

    public function rules(): array{
        return [
            'current_password' => 'nullable|string',
            'new_password' => 'required|string|min:8|confirmed',
        ];
    }
}

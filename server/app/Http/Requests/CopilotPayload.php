<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopilotPayload extends FormRequest{
    public function authorize(): bool{
        return true;
    }

    public function rules(): array{
        return [
            'messages' => ['required', 'array', 'min:1', 'max:10'],
            'messages.*.content' => ['required', 'string'],
            'messages.*.role' => ['required', 'string'],
            'history_id' => ['nullable', 'integer'],
        ];
    }
}

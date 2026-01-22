<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmWorkflowRequest extends FormRequest{

    public function authorize(): bool{
        return true;
    }

    public function rules(): array{
        return [
            "question" => "required|string",
            "workflow" => "required|array",
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkN8nAccountRequest extends FormRequest{

    public function authorize(): bool{
        return true;
    }

    public function rules(): array{
        return [
            'base_url' => 'required|url',
            'api_key' => 'required|string'
        ];
        
    }
}

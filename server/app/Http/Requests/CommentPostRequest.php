<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentPostRequest extends FormRequest{
  
    public function authorize(): bool{
        return true;
    }

    public function rules(): array{
        return [
            'content' => 'string|required'
        ];
    }
}

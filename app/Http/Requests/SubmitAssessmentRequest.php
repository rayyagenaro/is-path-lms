<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Jawab semua pertanyaan sebelum mengirim assessment.',
            'answers.*.required' => 'Pilih satu jawaban untuk pertanyaan ini.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Yorumları admin ve editör yönetir (route grubu)
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activity_id' => $this->input('activity_id') ?: null,
            'approved' => $this->boolean('approved'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:2000'],
            'activity_id' => ['nullable', 'exists:activities,id'],
            'approved' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => __('ad'), 'email' => __('e-posta'), 'rating' => __('puan'), 'comment' => __('yorum'), 'activity_id' => __('aktivite')];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Duyuruları admin ve editör yönetir (route grubu)
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'link' => trim((string) $this->input('link')) ?: null,
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:160'],
            'link' => ['nullable', 'string', 'max:500', 'regex:#^(https?://|/)#i'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['link.regex' => __('Bağlantı https:// ya da / ile başlamalı.')];
    }

    public function attributes(): array
    {
        return ['text' => __('duyuru metni'), 'link' => __('bağlantı')];
    }
}

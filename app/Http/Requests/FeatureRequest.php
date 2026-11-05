<?php

namespace App\Http\Requests;

use App\Support\FeatureIcons;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Özellik kartlarını admin ve editör yönetir (route grubu)
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
            'remove_icon_upload' => $this->boolean('remove_icon_upload'),
        ]);
    }

    public function rules(): array
    {
        return [
            'icon' => ['required', Rule::in([...array_keys(FeatureIcons::ICONS), 'custom'])],
            'icon_upload' => ['nullable', 'file', 'mimes:svg,png,webp', 'max:1024'],
            'remove_icon_upload' => ['boolean'],
            'title' => ['required', 'string', 'max:60'],
            'text' => ['nullable', 'string', 'max:160'],
            'active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['icon_upload' => __('ikon dosyası'), 'icon' => __('ikon'), 'title' => __('başlık'), 'text' => __('kısa yazı')];
    }
}

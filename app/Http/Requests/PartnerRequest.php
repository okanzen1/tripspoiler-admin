<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $partner = $this->route('partner');

        $this->merge([
            // Kod sadece oluştururken belirlenir: tıklama geçmişi bu koda bağlı
            'slug' => $partner?->slug ?? Str::slug($this->input('slug') ?: $this->input('name', ''), ''),
            'active' => $this->boolean('active'),
            'show_on_site' => $this->boolean('show_on_site'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_num', 'max:50', Rule::unique('partners')->ignore($this->route('partner'))],
            'website' => ['nullable', 'url', 'max:255'],
            // SVG'yi bazı sunucular "text/xml" sanar: SVG uzantı + içerikle tanınır, diğerleri gerçek resim olmalı
            'logo' => ['nullable', 'file', 'max:2048', function (string $attribute, $file, \Closure $fail) {
                $extension = strtolower($file->getClientOriginalExtension());
                $ok = $extension === 'svg'
                    ? str_contains((string) file_get_contents($file->getRealPath(), length: 4096), '<svg')
                    : in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) && in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true);
                if (!$ok) {
                    $fail(__('Logo SVG, PNG, JPG ya da WebP olmalı.'));
                }
            }],
            'remove_logo' => ['boolean'],
            'active' => ['boolean'],
            'show_on_site' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('partner adı'),
            'slug' => __('kod'),
            'website' => __('web sitesi'),
            'logo' => __('logo'),
        ];
    }
}

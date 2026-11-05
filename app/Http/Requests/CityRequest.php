<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        // Slug boş bırakılırsa addan üretilir
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name', '')),
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'country_id' => ['required', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('cities')->ignore($this->route('city'))],
            'active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'country_id' => __('ülke'),
            'name' => __('şehir adı'),
            'slug' => __('URL adı'),
        ];
    }
}

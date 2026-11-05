<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Blogları admin ve editör yönetir (route grubu)
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('title', '')),
            'active' => $this->boolean('active'),
            'city_id' => $this->input('city_id') ?: null,
            'published_at' => $this->input('published_at') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('blogs')->ignore($this->route('blog'))],
            'city_id' => ['nullable', 'exists:cities,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string', 'max:200000'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_cover' => ['boolean'],
            'activity_ids' => ['nullable', 'array'],
            'activity_ids.*' => ['integer', 'exists:activities,id'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'active' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            ...\App\Support\FaqSync::rules(),
        ];
    }

    public function messages(): array
    {
        return \App\Support\FaqSync::messages();
    }

    /**
     * Virgülle yazılan etiketler → temiz liste (tekrarsız).
     *
     * @return list<string>
     */
    public function tags(): array
    {
        return collect(explode(',', (string) $this->validated('tags')))
            ->map(fn ($tag) => trim($tag))->filter()->unique(fn ($tag) => mb_strtolower($tag))->values()->all();
    }

    public function attributes(): array
    {
        return [
            'title' => __('başlık'),
            'slug' => __('URL adı'),
            'city_id' => __('şehir'),
            'excerpt' => __('özet'),
            'content' => __('içerik'),
            'tags' => __('etiketler'),
            'cover' => __('kapak görseli'),
            'meta_title' => __('SEO başlığı'),
            'meta_description' => __('SEO açıklaması'),
            'published_at' => __('yayın tarihi'),
        ];
    }
}

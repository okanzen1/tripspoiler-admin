<?php

namespace App\Http\Requests;

use App\Enums\ActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
{
    public const MAX_IMAGES = 15;

    public const MAX_FAQS = 30;

    public function authorize(): bool
    {
        return true; // Aktiviteleri admin ve editör yönetir (route grubu)
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name', '')),
            'audio_guide' => $this->boolean('audio_guide'),
            'is_popular' => $this->boolean('is_popular'),
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        $activity = $this->route('activity');
        $existing = $activity ? $activity->images()->count() - count((array) $this->input('remove_images', [])) : 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('activities')->ignore($activity)],
            'type' => ['required', Rule::enum(ActivityType::class)],
            'city_id' => ['required', 'exists:cities,id'],
            'partner_id' => ['nullable', 'exists:partners,id'],
            'affiliate_url' => ['nullable', 'url', 'max:2000'],
            'partner_product_id' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:50'],
            'audio_guide' => ['boolean'],
            'setting' => ['nullable', Rule::in(['indoor', 'outdoor', 'both'])],
            'description' => ['nullable', 'string', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_popular' => ['boolean'],
            'active' => ['boolean'],
            'images' => ['nullable', 'array', 'max:' . max(0, self::MAX_IMAGES - $existing)],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'cover_image_id' => ['nullable', 'integer'],
            ...\App\Support\FaqSync::rules(self::MAX_FAQS),
            ...\App\Support\ReviewSync::rules(),
        ];
    }

    public function messages(): array
    {
        return [
            'images.max' => __('Bir aktivitede en fazla :count görsel olabilir.', ['count' => self::MAX_IMAGES]),
            ...\App\Support\FaqSync::messages(self::MAX_FAQS),
            ...\App\Support\ReviewSync::messages(),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('aktivite adı'),
            'slug' => __('URL adı'),
            'type' => __('tür'),
            'city_id' => __('şehir'),
            'partner_id' => __('partner'),
            'affiliate_url' => __('partner linki'),
            'partner_product_id' => __('partner ürün kodu'),
            'duration' => __('süre'),
            'description' => __('açıklama'),
            'meta_title' => __('SEO başlığı'),
            'meta_description' => __('SEO açıklaması'),
            'images.*' => __('görsel'),
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Activity;
use App\Models\Blog;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * SSS: "target" sorunun nerede görüneceği:
 *  general (anasayfa) · page:KEY (o sayfa, her şehirde) · page:KEY@CITY (o sayfa, sadece o şehir seçiliyken)
 *  city:ID (şehre göre değişen bütün sayfalar, o şehir seçiliyken) · activity:ID · blog:ID
 */
class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // SSS'leri admin ve editör yönetir (route grubu)
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // sayfa türü seçildi ama sayfa/şehir/aktivite seçilmediyse boş kalır ve "yer seç" hatası verir
            'target' => $this->input('target') ?: (in_array($this->input('target_kind'), [null, '', 'general'], true) ? 'general' : null),
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
            'target' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail) {
                [$type, $id] = array_pad(explode(':', (string) $value, 2), 2, null);
                $exists = match ($type) {
                    'general' => true,
                    'activity' => Activity::whereKey((int) $id)->exists(),
                    'blog' => Blog::whereKey((int) $id)->exists(),
                    'city' => \App\Models\City::whereKey((int) $id)->exists(),
                    'page' => (function () use ($id) {
                        [$page, $city] = array_pad(explode('@', (string) $id, 2), 2, null);

                        return \App\Support\PageSections::supports($page) && ($city === null || \App\Models\City::whereKey((int) $city)->exists());
                    })(),
                    default => false,
                };
                if (!$exists) {
                    $fail(__('Seçilen yer bulunamadı.'));
                }
            }],
            'active' => ['boolean'],
        ];
    }

    /**
     * @return array{activity_id: ?int, blog_id: ?int}
     */
    public function owner(): array
    {
        [$type, $id] = array_pad(explode(':', (string) $this->validated('target'), 2), 2, null);
        [$page, $pageCity] = $type === 'page' ? array_pad(explode('@', (string) $id, 2), 2, null) : [null, null];

        return [
            'activity_id' => $type === 'activity' ? (int) $id : null,
            'blog_id' => $type === 'blog' ? (int) $id : null,
            'city_id' => $type === 'city' ? (int) $id : ($pageCity !== null ? (int) $pageCity : null),
            'page_key' => $page,
        ];
    }

    public function messages(): array
    {
        return ['target.required' => __('Sorunun görüneceği yeri seç (sayfa, şehir, aktivite ya da yazı).')];
    }

    public function attributes(): array
    {
        return ['question' => __('soru'), 'answer' => __('cevap'), 'target' => __('yer')];
    }
}

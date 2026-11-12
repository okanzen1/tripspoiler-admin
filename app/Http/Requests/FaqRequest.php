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
            // "Toplu ekle": soru/cevap yerine "bulk" (yapıştırılan metin, App\Support\FaqParser)
            'question' => ['required_without:bulk', 'nullable', 'string', 'max:500'],
            'answer' => ['required_without:bulk', 'nullable', 'string', 'max:5000'],
            'bulk' => ['nullable', 'string', 'max:100000', function (string $attribute, mixed $value, Closure $fail) {
                $pairs = \App\Support\FaqParser::parse((string) $value);
                if (!$pairs) {
                    $fail(__('Soru bulunamadı. Her soru kendi satırında, cevabı altında olmalı.'));
                } elseif (count($pairs) > 100) {
                    $fail(__('Tek seferde en fazla 100 soru eklenebilir.'));
                } elseif (collect($pairs)->contains(fn ($p) => mb_strlen($p['question']) > 500 || mb_strlen($p['answer']) > 5000)) {
                    $fail(__('Bir soru 500, bir cevap 5000 karakterden uzun olamaz.'));
                }
            }],
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
        return ['question' => __('soru'), 'answer' => __('cevap'), 'target' => __('yer'), 'bulk' => __('sorular')];
    }
}

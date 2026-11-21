<?php

namespace App\Http\Requests;

use Closure;
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
            // "Toplu ekle": ad/puan/yorum yerine "bulk" (yapıştırılan satırlar, App\Support\ReviewParser)
            'name' => ['required_without:bulk', 'nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'rating' => ['required_without:bulk', 'nullable', 'integer', 'between:1,5'],
            'comment' => ['required_without:bulk', 'nullable', 'string', 'max:2000'],
            'bulk' => ['nullable', 'string', 'max:200000', function (string $attribute, mixed $value, Closure $fail) {
                ['reviews' => $reviews, 'invalid' => $invalid] = \App\Support\ReviewParser::parse((string) $value);
                if ($invalid) {
                    $fail(__('Şu satırlar okunamadı: :lines. Her satır "Ad | 4★ | Yorum | dil | 2026-07-29" biçiminde olmalı.', ['lines' => implode(', ', array_slice($invalid, 0, 10))]));
                } elseif (!$reviews) {
                    $fail(__('Yorum bulunamadı.'));
                } elseif (count($reviews) > 200) {
                    $fail(__('Tek seferde en fazla 200 yorum eklenebilir.'));
                } elseif (collect($reviews)->contains(fn ($r) => mb_strlen($r['name']) > 120 || mb_strlen($r['comment']) > 2000)) {
                    $fail(__('Bir ad 120, bir yorum 2000 karakterden uzun olamaz.'));
                } elseif (collect($reviews)->contains(fn ($r) => $r['date'] && $r['date'] > now()->toDateString())) {
                    $fail(__('Yorum tarihi bugünden ileri olamaz.'));
                }
            }],
            'activity_id' => ['nullable', 'exists:activities,id'],
            'approved' => ['boolean'],
            'review_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => __('ad'), 'email' => __('e-posta'), 'rating' => __('puan'), 'comment' => __('yorum'), 'activity_id' => __('aktivite'), 'review_date' => __('yorum tarihi'), 'bulk' => __('yorumlar')];
    }
}

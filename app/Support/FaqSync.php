<?php

namespace App\Support;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sihirbazdaki SSS listesini (aktivite ya da blog) kaydeder: formdaki sıra korunur, formdan çıkarılanlar
 * silinir, soru/cevap İngilizce yazılır; mevcut soruların diğer dil çevirileri ve yayın durumu korunur.
 * Soru ya da cevabı boş satırlar atlanır.
 */
class FaqSync
{
    /**
     * @param  HasMany<Faq>  $relation  ör. $activity->faqs()
     * @param  array<int, array{id?: mixed, question?: ?string, answer?: ?string}>  $rows
     */
    public static function sync(HasMany $relation, array $rows): void
    {
        $rows = collect($rows)
            ->filter(fn ($row) => filled($row['question'] ?? null) && filled($row['answer'] ?? null))
            ->values();
        $existing = $relation->get()->keyBy('id');

        (clone $relation)->whereNotIn('id', $rows->pluck('id')->filter())->delete();

        foreach ($rows as $index => $row) {
            $faq = $existing->get((int) ($row['id'] ?? 0)) ?? $relation->make(['active' => true]);
            $faq->setTranslation('question', 'en', trim($row['question']));
            $faq->setTranslation('answer', 'en', trim($row['answer']));
            $faq->sort_order = $index;
            $faq->save();
        }
    }

    /**
     * Sihirbaz doğrulama kuralları ("faqs" alanı).
     */
    public static function rules(int $max = 30): array
    {
        return [
            'faqs' => ['nullable', 'array', 'max:' . $max],
            'faqs.*.id' => ['nullable', 'integer'],
            'faqs.*.question' => ['nullable', 'required_with:faqs.*.answer', 'string', 'max:500'],
            'faqs.*.answer' => ['nullable', 'required_with:faqs.*.question', 'string', 'max:5000'],
        ];
    }

    public static function messages(int $max = 30): array
    {
        return [
            'faqs.max' => "En fazla {$max} soru eklenebilir.",
            'faqs.*.question.required_with' => __('Cevabı yazılan her sorunun sorusu da olmalı.'),
            'faqs.*.answer.required_with' => __('Her sorunun cevabını yaz.'),
        ];
    }
}

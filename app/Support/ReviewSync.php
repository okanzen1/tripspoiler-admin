<?php

namespace App\Support;

use App\Models\Activity;

/**
 * Aktivite sihirbazındaki yorum listesini kaydeder: formdan çıkarılanlar silinir, mevcutlar güncellenir
 * (e-posta ve tarih korunur), yeni satırlar elle eklenmiş yorum olarak kaydedilir. Boş satırlar atlanır.
 */
class ReviewSync
{
    public static function sync(Activity $activity, array $rows): void
    {
        $rows = collect($rows)->filter(fn ($row) => filled($row['name'] ?? null) && filled($row['comment'] ?? null))->values();
        $existing = $activity->reviews()->get()->keyBy('id');

        $activity->reviews()->whereNotIn('id', $rows->pluck('id')->filter())->delete();

        foreach ($rows as $row) {
            $review = $existing->get((int) ($row['id'] ?? 0)) ?? $activity->reviews()->make();
            $review->fill([
                'name' => trim($row['name']),
                'rating' => (int) $row['rating'],
                'comment' => trim($row['comment']),
                'approved' => (bool) ($row['approved'] ?? false),
            ])->save();
        }
    }

    public static function rules(): array
    {
        return [
            'reviews' => ['nullable', 'array', 'max:200'],
            'reviews.*.id' => ['nullable', 'integer'],
            'reviews.*.name' => ['nullable', 'required_with:reviews.*.comment', 'string', 'max:120'],
            'reviews.*.rating' => ['nullable', 'integer', 'between:1,5'],
            'reviews.*.comment' => ['nullable', 'required_with:reviews.*.name', 'string', 'max:2000'],
            'reviews.*.approved' => ['nullable', 'boolean'],
        ];
    }

    public static function messages(): array
    {
        return [
            'reviews.*.name.required_with' => __('Her yorumun yazan kişisini (ad) yaz.'),
            'reviews.*.comment.required_with' => __('Adı yazılan her yorumun metni de olmalı.'),
        ];
    }
}

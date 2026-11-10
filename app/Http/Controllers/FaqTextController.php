<?php

namespace App\Http\Controllers;

use App\Models\SectionText;
use Illuminate\Http\Request;

/**
 * SSS bölümünün yazıları (sitenin her sayfasındaki SSS bölümünde ortak): solda başlık (section_texts "faq")
 * ve altındaki "Aklında başka bir soru mu var?" kutusu ("faq_contact": title, description, eyebrow = düğme yazısı).
 * İngilizce yazılır, Çeviriler kutusundan 12 dile çevrilir. Boş alan sitede varsayılan yazıyı gösterir.
 */
class FaqTextController extends Controller
{
    /** kayıt => [alan => [etiket, varsayılan (sitedeki), en fazla]] */
    public const FIELDS = [
        'faq' => [
            'eyebrow' => ['Üstteki küçük yazı', 'FAQ', 40],
            'title' => ['Başlık (siyah kısım)', 'Questions,', 60],
            'title_highlight' => ['Başlık (kırmızı kısım)', 'answered.', 60],
            'description' => ['Açıklama', 'Quick answers to what travelers ask us most.', 260],
        ],
        'faq_contact' => [
            'title' => ['Kutunun başlığı', 'Still have a question?', 60],
            'description' => ['Kutunun yazısı', 'Write to us; we usually reply within a day.', 160],
            'eyebrow' => ['Düğme yazısı', 'Contact us', 30],
        ],
    ];

    public function edit()
    {
        return view('admin.faqs.texts', [
            'records' => collect(self::FIELDS)->map(fn ($fields, $key) => SectionText::for($key)),
        ]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (self::FIELDS as $key => $fields) {
            foreach ($fields as $field => [, , $max]) {
                $rules["{$key}.{$field}"] = ['nullable', 'string', 'max:' . $max];
            }
        }
        $data = $request->validate($rules);

        foreach (self::FIELDS as $key => $fields) {
            $record = SectionText::for($key);
            foreach (array_keys($fields) as $field) {
                $value = trim((string) ($data[$key][$field] ?? ''));
                $value !== '' ? $record->setTranslation($field, 'en', $value) : $record->forgetTranslation($field, 'en');
            }
            $record->save();
        }

        return back()->with('success', __('SSS bölümünün yazıları kaydedildi.'));
    }
}

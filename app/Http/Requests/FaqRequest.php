<?php

namespace App\Http\Requests;

use App\Models\Activity;
use App\Models\Blog;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * SSS: "target" sorunun nerede görüneceği: "general", "activity:ID" ya da "blog:ID".
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
            'target' => $this->input('target') ?: 'general',
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
                    'page' => \App\Support\PageSections::supports((string) $id),
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

        return [
            'activity_id' => $type === 'activity' ? (int) $id : null,
            'blog_id' => $type === 'blog' ? (int) $id : null,
            'city_id' => $type === 'city' ? (int) $id : null,
            'page_key' => $type === 'page' ? (string) $id : null,
        ];
    }

    public function attributes(): array
    {
        return ['question' => __('soru'), 'answer' => __('cevap'), 'target' => __('yer')];
    }
}

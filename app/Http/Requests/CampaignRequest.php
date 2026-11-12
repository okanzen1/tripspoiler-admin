<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Kampanyaları admin ve editör yönetir (route grubu)
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // Kod her zaman büyük harf (Türkçe i/ı sorunu olmasın diye İngilizce kuralla: trippass → TRIPPASS)
            'code' => strtoupper(trim((string) $this->input('code'))) ?: null,
            'url' => trim((string) $this->input('url')) ?: null,
            'starts_at' => $this->input('starts_at') ?: null,
            'ends_at' => $this->input('ends_at') ?: null,
            'partner_id' => $this->input('partner_id') ?: null,
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'badge' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:300'],
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'code' => ['nullable', 'string', 'max:60', 'required_without:url'],
            'url' => ['nullable', 'string', 'max:1000', 'regex:#^(https?://|/)#i', 'required_without:code'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.regex' => __('Bağlantı https:// ya da / ile başlamalı.'),
            'code.required_without' => __('İndirim kodu ya da bağlantıdan en az biri olmalı.'),
            'url.required_without' => __('İndirim kodu ya da bağlantıdan en az biri olmalı.'),
            'ends_at.after_or_equal' => __('Bitiş tarihi başlangıçtan önce olamaz.'),
        ];
    }

    public function attributes(): array
    {
        return ['title' => __('başlık'), 'description' => __('açıklama'), 'badge' => __('etiket'), 'note' => __('not'), 'code' => __('indirim kodu'), 'url' => __('bağlantı')];
    }
}

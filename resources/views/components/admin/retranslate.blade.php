{{--
    "Yeniden çevir": tüm diller güncel olsa da İngilizceden baştan çevirir (all=1, arka planda; sağ üstte bildirim).
    Form sayfanın sonuna eklenir (@stack('forms')), buton form="…" ile bağlanır: başka bir formun içinde de kullanılabilir.
--}}
@props(['type', 'model'])
@php($formId = 'retranslate-' . $type . '-' . $model->getKey())

<button type="submit" form="{{ $formId }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-sm font-semibold text-purple-700 ring-1 ring-purple-200 transition hover:bg-purple-50']) }}>
    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 0 1 15.5-6.2L21 8M21 3v5h-5M21 12a9 9 0 0 1-15.5 6.2L3 16M3 21v-5h5" /></svg>
    {{ __('Yeniden çevir') }}
</button>

@push('forms')
    <form id="{{ $formId }}" action="{{ route('translations.translate', [$type, $model->getKey()]) }}" method="POST" class="hidden"
        data-translate="{{ $type }}:{{ $model->getKey() }}"
        data-confirm-title="{{ __('Tüm diller baştan çevrilsin mi?') }}"
        data-confirm="{{ __('Güncel olsalar da tüm dillerdeki metin İngilizceden Google Çeviri ile yeniden çevrilir. Dillerde elle yaptığın düzeltmeler gider.') }}"
        data-confirm-button="{{ __('Evet, yeniden çevir') }}">
        @csrf
        <input type="hidden" name="all" value="1">
    </form>
@endpush

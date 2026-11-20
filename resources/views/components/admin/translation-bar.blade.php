@props(['type', 'model'])

{{--
    Kayıt düzenleme sayfasındaki çeviri kutusu: her dil bir kart (bayrak, ad, durum) → o dilin metnini görüp
    düzelteceğin sayfaya gider. Sağ üstte "Tüm dillere çevir". Slug çevrilmez (her dilde aynı adres).
--}}
@php
    $status = app(\App\Services\Translation\ModelTranslator::class)->fullStatus($model); // kayıt + SSS'leri
    $pending = count(array_filter($status, fn ($s) => $s !== 'ok'));
    $languages = \App\Models\Language::targets();
    $states = [
        'ok' => [__('Güncel'), 'bg-emerald-500', 'text-emerald-700'],
        'stale' => [__('İngilizce değişti'), 'bg-amber-500', 'text-amber-700'],
        'missing' => [__('Çevrilmedi'), 'bg-slate-300', 'text-slate-400'],
    ];
    // bu kaydın boş kalan SEO / içerik alanları (App\Support\ContentTodo)
    $contentMissing = \App\Support\ContentTodo::missing($type, $model->getKey());
@endphp

@if ($contentMissing)
    <div class="mb-3 flex items-start gap-3 rounded-2xl bg-amber-50 px-4 py-3 text-sm ring-1 ring-amber-200">
        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-amber-500 text-white">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /></svg>
        </span>
        <p class="text-amber-900"><span class="font-semibold">{{ __('Eksik: :list', ['list' => implode(', ', $contentMissing)]) }}</span>
            <span class="block text-amber-800">{{ __('Bu alanlar boş kalırsa sayfa Google\'da eksik görünür. Doldurup kaydet.') }}</span></p>
    </div>
@endif

<x-admin.card {{ $attributes->class('mb-5 overflow-hidden')->merge(['data-live' => 'translations-' . $type . '-' . $model->getKey()]) }}>
    <div class="flex flex-wrap items-center gap-x-6 gap-y-3 px-5 py-4">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-purple-500 text-white">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6" /></svg>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900">
                    {{ __('Çeviriler') }}
                    @if ($status)
                        <span class="ml-1 font-normal text-slate-500">· {{ __(':ok / :total dil güncel', ['ok' => count($status) - $pending, 'total' => count($status)]) }}</span>
                    @endif
                </p>
                <p class="text-xs text-slate-500">{{ __('Bu sayfada İngilizce düzenlenir. Bir dile tıkla: metnini gör, düzelt ya da yeniden çevir.') }}</p>
            </div>
        </div>

        @if ($pending > 0)
            <form action="{{ route('translations.translate', [$type, $model->getKey()]) }}" method="POST" data-translate="{{ $type }}:{{ $model->getKey() }}"
                data-confirm-title="{{ __(':count dile çevrilsin mi?', ['count' => $pending]) }}"
                data-confirm="{{ __('Çevirisi olmayan ya da İngilizcesi değişen diller Google Çeviri ile çevrilir. Birkaç dakika sürebilir.') }}"
                data-confirm-button="{{ __('Evet, çevir') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700 disabled:cursor-wait disabled:opacity-70">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6" /></svg>
                    <span>{{ $pending === count($status) ? __('Tüm dillere çevir') : __('Eksik :count dili çevir', ['count' => $pending]) }}</span>
                </button>
            </form>
        @elseif ($status)
            <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-700">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7" /></svg>
                {{ __('Tüm çeviriler güncel') }}
            </span>
            <x-admin.retranslate :type="$type" :model="$model" />
            </div>
        @endif
    </div>

    @if ($status)
        <nav class="grid grid-cols-2 gap-px border-t border-slate-100 bg-slate-100 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6" aria-label="{{ __('Dillerdeki metni gör ve düzelt') }}">
            @foreach ($languages as $language)
                @php([$label, $dot, $text] = $states[$status[$language->code] ?? 'missing'])
                <a href="{{ route('translations.edit', [$type, $model->getKey(), $language->code]) }}"
                    class="group flex items-center gap-3 bg-surface px-4 py-3 transition hover:bg-white">
                    <span class="text-2xl leading-none">{{ $language->flag() }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-slate-900 group-hover:text-brand-900">{{ $language->native_name }}</span>
                        <span class="flex items-center gap-1.5 text-xs {{ $text }}"><span class="size-1.5 shrink-0 rounded-full {{ $dot }}"></span>{{ $label }}</span>
                    </span>
                    <span class="text-xs font-medium text-slate-400 opacity-0 transition group-hover:opacity-100">{{ __('Aç') }} →</span>
                </a>
            @endforeach
        </nav>
    @else
        <p class="border-t border-slate-100 px-5 py-3 text-sm text-slate-500">{{ __('Sitede İngilizceden başka aktif dil yok.') }}</p>
    @endif
</x-admin.card>

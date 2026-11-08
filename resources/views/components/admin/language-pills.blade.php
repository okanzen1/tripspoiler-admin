{{--
    Bir kaydın dilleri: her dil bir kutucuk (yeşil = güncel, sarı = İngilizce değişti, gri = çevrilmedi);
    tıklayınca o dildeki metni görüp elle düzeltme sayfası açılır. Çeviri bitince yerinde yenilenir (data-live).
--}}
@props(['type', 'model'])
@php
    $status = app(\App\Services\Translation\ModelTranslator::class)->status($model);
    $dots = ['ok' => 'bg-emerald-500', 'stale' => 'bg-amber-500'];
    $titles = ['ok' => __('Güncel'), 'stale' => __('İngilizce değişti, yeniden çevir')];
@endphp
<div {{ $attributes->class('flex flex-wrap items-center gap-1.5') }} data-live="langs-{{ $type }}-{{ $model->getKey() }}">
    <span class="mr-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('Diller') }}</span>
    @foreach (\App\Models\Language::targets() as $language)
        @php $state = $status[$language->code] ?? 'missing'; @endphp
        <a href="{{ route('translations.edit', [$type, $model->getKey(), $language->code]) }}"
            title="{{ $language->native_name }} · {{ $titles[$state] ?? __('Çevrilmedi') }}"
            class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200 transition hover:ring-brand-300">
            <span class="size-1.5 rounded-full {{ $dots[$state] ?? 'bg-slate-300' }}"></span>{{ $language->native_name }}
        </a>
    @endforeach
</div>

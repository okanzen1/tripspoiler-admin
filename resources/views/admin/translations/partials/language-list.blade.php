{{-- Çeviri sayfasının solundaki dil listesi: bayrak, ad, durum. Seçili dil koyu. --}}
@php
    $states = [
        'ok' => [__('Güncel'), 'bg-emerald-500', 'text-emerald-700'],
        'stale' => [__('İngilizce değişti'), 'bg-amber-500', 'text-amber-700'],
        'missing' => [__('Çevrilmedi'), 'bg-slate-300', 'text-slate-400'],
    ];
@endphp

<x-admin.card class="overflow-hidden lg:sticky lg:top-20">
    <p class="border-b border-slate-100 px-4 py-3 text-xs font-semibold text-slate-500">{{ __('Diller') }}</p>
    <nav class="p-2" aria-label="{{ __('Diller') }}">
        <a href="{{ $owner['url'] }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:bg-slate-50">
            <span class="text-xl leading-none">🇬🇧</span>
            <span class="min-w-0 flex-1">
                <span class="block font-semibold text-slate-900">English</span>
                <span class="block text-xs text-slate-500">{{ __('Kaynak — kayıt sayfasında düzenlenir') }}</span>
            </span>
        </a>
        <div class="my-1.5 border-t border-slate-100"></div>
        @foreach ($languages as $item)
            @php([$label, $dot, $text] = $states[$status[$item->code] ?? 'missing'])
            @php($current = $item->code === $language->code)
            <a href="{{ route('translations.edit', [$type, $model->getKey(), $item->code]) }}" @if ($current) aria-current="page" @endif
                @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition', 'bg-brand-900 text-white shadow-sm' => $current, 'hover:bg-slate-50' => !$current])>
                <span class="text-xl leading-none">{{ $item->flag() }}</span>
                <span class="min-w-0 flex-1">
                    <span @class(['block truncate font-semibold', 'text-slate-900' => !$current])>{{ $item->native_name }}</span>
                    <span @class(['flex items-center gap-1.5 text-xs', 'text-white/70' => $current, $text => !$current])>
                        <span @class(['size-1.5 shrink-0 rounded-full', $dot])></span>{{ $label }}
                    </span>
                </span>
                @if ($current)
                    <svg class="size-4 shrink-0 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                @endif
            </a>
        @endforeach
    </nav>
</x-admin.card>

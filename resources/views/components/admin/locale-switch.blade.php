{{-- Panel dili seçici: bayraklı açılır menü; seçim kullanıcıya kaydedilir (LocaleController) --}}
@php
    $locales = config('app.panel_locales');
    $flags = ['tr' => '🇹🇷', 'en' => '🇬🇧'];
    $current = app()->getLocale();
@endphp

<details data-dropdown {{ $attributes->class('group relative') }}>
    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:ring-brand-200 group-open:ring-2 group-open:ring-brand-900/20 [&::-webkit-details-marker]:hidden"
        aria-label="{{ __('Panel dili') }}">
        <svg class="size-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" /></svg>
        <span class="text-base leading-none">{{ $flags[$current] ?? '' }}</span>
        <span>{{ $locales[$current] ?? strtoupper($current) }}</span>
        <svg class="size-4 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
    </summary>

    <form action="{{ route('locale.update') }}" method="POST"
        class="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-2xl bg-white p-1.5 shadow-xl ring-1 ring-slate-200">
        @csrf
        <p class="px-3 pb-1.5 pt-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Panel dili') }}</p>
        @foreach ($locales as $code => $name)
            <button type="submit" name="locale" value="{{ $code }}" lang="{{ $code }}" @if ($code === $current) aria-current="true" @endif
                @class([
                    'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition',
                    'bg-brand-50 font-semibold text-brand-900' => $code === $current,
                    'text-slate-700 hover:bg-slate-50' => $code !== $current,
                ])>
                <span class="text-lg leading-none">{{ $flags[$code] ?? '' }}</span>
                <span class="flex-1">{{ $name }}</span>
                @if ($code === $current)
                    <svg class="size-4 text-brand-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7" /></svg>
                @endif
            </button>
        @endforeach
    </form>
</details>

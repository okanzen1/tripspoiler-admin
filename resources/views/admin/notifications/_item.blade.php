{{-- Tek bildirim satırı (zil ve Bildirimler sayfası). JS aynı yapıyı resources/js/notifications.js'te üretir. --}}
<li>
    <a href="{{ $item['open'] }}" @class(['flex items-start gap-3 transition hover:bg-slate-50', 'px-4 py-3' => $compact ?? false, 'px-5 py-4' => !($compact ?? false), 'bg-brand-50/40' => !$item['read']])>
        <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg {{ $item['tone'] }}">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}" /></svg>
        </span>
        <span class="min-w-0 flex-1">
            <span @class(['block text-sm text-slate-900', 'font-semibold' => !$item['read'], 'font-medium' => $item['read']])>{{ $item['title'] }}</span>
            @if ($item['body'])
                <span @class(['block text-xs text-slate-500', 'truncate' => $compact ?? false])>{{ $item['body'] }}</span>
            @endif
            <span class="mt-0.5 block text-[11px] text-slate-400" title="{{ $item['date'] }}">{{ $item['label'] }} · {{ $item['time'] }}</span>
        </span>
        @unless ($item['read'])
            <span class="mt-2 size-2 shrink-0 rounded-full bg-brand-600" aria-label="{{ __('Okunmadı') }}"></span>
        @endunless
    </a>
</li>

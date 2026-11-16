@props(['count' => 0, 'text', 'href' => null, 'link' => null, 'tone' => 'rose'])

{{-- Menüdeki sayının bu sayfadaki açıklaması: "1 yazının kapak resmi eksik → Göster" --}}
@if ($count > 0)
    @php
        $tones = [
            'rose' => ['bg-rose-50 ring-rose-200 text-rose-800', 'bg-rose-500', 'bg-rose-600 hover:bg-rose-700'],
            'sky' => ['bg-sky-50 ring-sky-200 text-sky-800', 'bg-sky-500', 'bg-sky-600 hover:bg-sky-700'],
        ];
        [$box, $dot, $btn] = $tones[$tone] ?? $tones['rose'];
    @endphp
    <div {{ $attributes->class(['mb-5 flex flex-wrap items-center gap-3 rounded-2xl px-5 py-3.5 text-sm ring-1', $box]) }}>
        <span class="grid min-w-7 place-items-center rounded-full {{ $dot }} px-2 py-0.5 text-xs font-bold text-white">{{ $count > 99 ? '99+' : $count }}</span>
        <p class="min-w-0 flex-1 font-medium">{{ $text }}</p>
        @if ($href)
            <a href="{{ $href }}" class="inline-flex shrink-0 items-center gap-1 rounded-lg {{ $btn }} px-3 py-1.5 text-xs font-semibold text-white transition">{{ $link ?? __('Göster') }} →</a>
        @endif
    </div>
@endif

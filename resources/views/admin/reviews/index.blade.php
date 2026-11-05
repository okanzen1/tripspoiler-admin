@extends('layouts.admin')

@section('title', 'Yorumlar')

@section('header')
    <x-admin.page-header :title="__('Yorumlar')" :description="__('Ziyaretçi yorumları onaylanınca sitede görünür. Onay bekleyenler listenin başında.')">
        <x-slot:icon><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z" /><path d="M8.5 11h.01M12 11h.01M15.5 11h.01" /></x-slot:icon>
        @if ($pageId)
            <a href="{{ route('pages.edit', $pageId) }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">{{ __('Yorumlar sayfası') }}</a>
        @endif
        <a href="{{ route('reviews.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yorum ekle') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    {{-- ÖZET --}}
    @php
        $maxN = max(1, (int) collect($distribution)->max());
    @endphp
    <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <x-admin.card class="flex items-center gap-5 p-6">
            <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-amber-100 text-amber-600">
                <svg class="size-7" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/></svg>
            </span>
            <div>
                <p class="text-3xl font-bold tabular-nums text-slate-900">{{ number_format($average, 1, ',', '.') }}</p>
                <div class="mt-0.5 flex items-center gap-2"><x-admin.stars :rating="(int) round($average)" /><span class="text-xs text-slate-500">{{ __(':count onaylı yorum', ['count' => $counts['approved']]) }}</span></div>
            </div>
        </x-admin.card>
        <a href="{{ route('reviews.index', ['status' => 'pending']) }}" class="block">
            <x-admin.card @class(['flex h-full items-center gap-5 p-6 transition hover:-translate-y-0.5', 'ring-2 ring-amber-300' => $counts['pending']])>
                <span @class(['grid size-14 shrink-0 place-items-center rounded-2xl', 'bg-amber-500 text-white' => $counts['pending'], 'bg-emerald-100 text-emerald-600' => !$counts['pending']])>
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="{{ $counts['pending'] ? 'M12 7v5l3 2' : 'm8 12 3 3 5-6' }}"/></svg>
                </span>
                <div>
                    <p class="text-3xl font-bold tabular-nums text-slate-900">{{ $counts['pending'] }}</p>
                    <p class="text-sm text-slate-500">{{ $counts['pending'] ? __('onay bekliyor · incele →') : __('Onay bekleyen yok') }}</p>
                </div>
            </x-admin.card>
        </a>
        <x-admin.card class="p-5">
            <ul class="space-y-1.5">
                @foreach ([5, 4, 3, 2, 1] as $r)
                    @php
                        $n = (int) ($distribution[$r] ?? 0);
                    @endphp
                    <li>
                        <a href="{{ route('reviews.index', ['rating' => $r]) }}" class="flex items-center gap-3 rounded-lg px-1 text-xs transition hover:bg-slate-50">
                            <span class="w-6 font-semibold text-slate-700">{{ $r }}★</span>
                            <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full bg-amber-400" style="width: {{ $n / $maxN * 100 }}%"></span></span>
                            <span class="w-5 text-right tabular-nums text-slate-500">{{ $n }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    </div>

    <x-admin.card class="overflow-hidden">
        <x-admin.filter-bar :search="$search" placeholder="Ad ya da yorumda ara…"
            :tabs="['name' => 'status', 'value' => $status, 'options' => ['' => ['Tümü', $counts['pending'] + $counts['approved']], 'pending' => ['Onay bekleyen', $counts['pending']], 'approved' => ['Onaylı', $counts['approved']]]]"
            :filters="[
                ['name' => 'activity', 'label' => 'Yer', 'value' => $activityId, 'options' => ['general' => __('Genel (site hakkında)')] + $activities->mapWithKeys(fn ($a) => [$a->id => \Illuminate\Support\Str::limit($a->name, 45)])->all(), 'icon' => 'ticket'],
                ['name' => 'rating', 'label' => 'Puan', 'value' => $rating, 'options' => [5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★'], 'icon' => 'star'],
            ]" />

        @if ($reviews->isEmpty())
            <div class="border-t border-slate-100 px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ $status === 'pending' ? __('Onay bekleyen yorum yok') . ' 🎉' : __('Yorum bulunamadı') }}</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100 border-t border-slate-100">
                @foreach ($reviews as $review)
                    <li @class(['flex items-start gap-4 px-6 py-4 transition hover:bg-slate-50/60', 'bg-amber-50/40' => !$review->approved])>
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-50 text-sm font-semibold text-brand-900">{{ $review->initials() }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="font-semibold text-slate-900">{{ $review->name }}</span>
                                <x-admin.stars :rating="$review->rating" />
                                <span class="text-xs text-slate-500">{{ $review->created_at->locale(app()->getLocale())->translatedFormat('j M Y H:i') }}</span>
                                @if ($review->activity)
                                    <a href="{{ route('activities.edit', $review->activity) }}" class="max-w-72 truncate rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-900 hover:underline">{{ $review->activity->name }}</a>
                                @else
                                    <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('Genel') }}</span>
                                @endif
                                @unless ($review->approved)
                                    <span class="rounded-md bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ __('Onay bekliyor') }}</span>
                                @endunless
                            </div>
                            <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-slate-700" dir="auto">{{ $review->comment }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5 pt-1">
                            <span class="text-xs text-slate-500">{{ __('Onaylı') }}</span>
                            <x-admin.toggle :action="route('reviews.toggle', $review)" :active="$review->approved" :label="__('Onaylı')" />
                            <a href="{{ route('reviews.edit', $review) }}" title="{{ __('Düzenle') }}" aria-label="{{ __('Düzenle') }}"
                                class="ml-2 grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-brand-900">
                                <x-admin.icon name="pencil" class="size-[18px]" />
                            </a>
                            <form action="{{ route('reviews.destroy', $review) }}" method="POST" data-confirm="{{ __(':name adlı kişinin yorumu silinsin mi?', ['name' => $review->name]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="{{ __('Sil') }}" aria-label="{{ __('Sil') }}" class="grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                    <x-admin.icon name="trash" class="size-[18px]" />
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="flex items-center justify-between border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                <span>{{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $reviews->total() }}</strong> {{ __('yorum') }}</span>
                @if ($reviews->hasPages()) {{ $reviews->links() }} @endif
            </div>
        @endif
    </x-admin.card>
@endsection

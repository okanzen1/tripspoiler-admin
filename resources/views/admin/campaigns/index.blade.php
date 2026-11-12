@extends('layouts.admin')

@section('title', 'Kampanyalar')

@section('header')
    <x-admin.page-header :title="__('Kampanyalar')" :description="__('İndirim kodları ve kampanya bağlantıları. Yayında kampanya varsa sitede üst menüde, yan menüde ve footer\'da görünür; yoksa hepsi gizlenir.')">
        <x-slot:icon><path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M2.5 7.5h19v4.5h-19zM12 21V7.5M12 7.5H8a2.5 2.5 0 1 1 0-5c2.5 0 4 5 4 5zM12 7.5h4a2.5 2.5 0 1 0 0-5c-2.5 0-4 5-4 5z" /></x-slot:icon>
        <a href="{{ config('site.url') }}/en/campaigns" target="_blank" rel="noopener"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-brand-900">
            {{ __('Sitede aç') }} ↗
        </a>
        <a href="{{ route('campaigns.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni kampanya') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    @php
        $states = [
            'live' => [__('Yayında'), 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'scheduled' => [__('Başlamadı'), 'bg-sky-50 text-sky-700 ring-sky-200'],
            'expired' => [__('Süresi doldu'), 'bg-slate-100 text-slate-500 ring-slate-200'],
            'off' => [__('Kapalı'), 'bg-slate-100 text-slate-500 ring-slate-200'],
        ];
        $live = $campaigns->filter(fn ($c) => $c->status() === 'live')->count();
    @endphp

    @if ($campaigns->isNotEmpty() && !$live)
        <div class="mb-5 rounded-2xl bg-amber-50 px-5 py-3.5 text-sm text-amber-900 ring-1 ring-amber-200">
            {{ __('Şu an yayında kampanya yok: sitede Kampanyalar bağlantıları gizli, /campaigns adresi anasayfaya yönleniyor.') }}
        </div>
    @endif

    <x-admin.card class="overflow-hidden">
        @if ($campaigns->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ __('Henüz kampanya yok') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('İlk kampanyayı ekle (ör. Megapass indirim kodu). Eklenene kadar sitede Kampanyalar bağlantısı görünmez.') }}</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($campaigns as $campaign)
                    @php [$stateLabel, $stateClass] = $states[$campaign->status()]; @endphp
                    <li class="flex items-start gap-4 px-6 py-4 transition hover:bg-slate-50/60">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-sm">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M2.5 7.5h19v4.5h-19zM12 21V7.5M12 7.5H8a2.5 2.5 0 1 1 0-5c2.5 0 4 5 4 5zM12 7.5h4a2.5 2.5 0 1 0 0-5c-2.5 0-4 5-4 5z" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md px-2 py-0.5 text-xs font-semibold ring-1 {{ $stateClass }}">{{ $stateLabel }}</span>
                                @if ($campaign->partner)
                                    <span class="rounded-md bg-lime-50 px-2 py-0.5 text-xs font-medium text-lime-700">{{ $campaign->partner->name }}</span>
                                @endif
                                @if ($campaign->badge)
                                    <span class="rounded-md bg-orange-50 px-2 py-0.5 text-xs font-semibold text-orange-700">{{ $campaign->badge }}</span>
                                @endif
                                @if ($campaign->code)
                                    <code class="rounded-md border border-dashed border-slate-300 bg-white px-2 py-0.5 text-xs font-bold tracking-wider text-slate-800">{{ $campaign->code }}</code>
                                @endif
                            </div>
                            <p class="mt-1.5 font-semibold text-slate-900">{{ $campaign->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                @if ($campaign->starts_at || $campaign->ends_at)
                                    {{ $campaign->starts_at?->translatedFormat('j M Y') ?? '…' }} – {{ $campaign->ends_at?->translatedFormat('j M Y') ?? __('süresiz') }}
                                @else
                                    {{ __('Süresiz') }}
                                @endif
                                @if ($campaign->url)
                                    · <span class="truncate" title="{{ $campaign->url }}">{{ \Illuminate\Support\Str::limit($campaign->url, 60) }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5 pt-1">
                            <x-admin.toggle :action="route('campaigns.toggle', $campaign)" :active="$campaign->active" :label="__('Yayında')" />
                            <a href="{{ route('campaigns.edit', $campaign) }}" title="{{ __('Düzenle') }}" aria-label="{{ __('Düzenle') }}"
                                class="ml-2 grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-brand-900">
                                <x-admin.icon name="pencil" class="size-[18px]" />
                            </a>
                            <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" data-confirm="{{ __('Bu kampanya silinsin mi?') }}">
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
            <div class="border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                {{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $campaigns->count() }}</strong> {{ __('kampanya') }},
                <strong class="font-semibold text-slate-700">{{ $live }}</strong> {{ __('sitede yayında') }}
            </div>
        @endif
    </x-admin.card>
@endsection

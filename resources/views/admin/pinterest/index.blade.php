@extends('layouts.admin')

@section('title', 'Pinterest')

@php
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $btn = 'inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800';
    $step = fn ($n, $done) => '<span class="grid size-7 shrink-0 place-items-center rounded-full text-xs font-bold ' . ($done ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500') . '">' . ($done ? '✓' : $n) . '</span>';
@endphp

@section('header')
    <x-admin.page-header :title="__('Pinterest')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Yayındaki blog yazıları sırayla Pinterest\'e pin olarak gider (İngilizce başlık, kapak resmi ve sitedeki bağlantı). Bir kez giden blog otomatik olarak tekrar gitmez; blog sayfasından elle tekrar gönderebilirsin.')">
        <x-slot:icon><circle cx="12" cy="12" r="9" /><path d="M10.5 8.5c3-1.5 6 0 5.5 3s-3 4-4.5 3M11.5 10l-2.5 11" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    {{-- DURUM --}}
    <div class="grid gap-4 sm:grid-cols-4">
        @foreach ([
            [__('Bugün gönderilen'), $sentToday . ' / ' . $daily, $enabled ? __('Otomatik gönderim açık') : __('Otomatik gönderim kapalı')],
            [__('Sırada bekleyen'), $queueCount, __('Yayında ve kapak resmi olan')],
            [__('Gönderilen blog'), $sentBlogs, __('Bir kez giden tekrar gitmez')],
            [__('Kapak resmi yok'), $noCover, __('Resim eklenince sıraya girer')],
        ] as [$label, $value, $hint])
            <x-admin.card class="p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-extrabold tabular-nums text-slate-900">{{ $value }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ $hint }}</p>
            </x-admin.card>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- 1. UYGULAMA --}}
        <x-admin.card class="p-6">
            <div class="flex items-start gap-3">
                {!! $step(1, $hasApp) !!}
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Pinterest uygulaması') }}</h2>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        {{ __('developers.pinterest.com › My apps › Connect app ile bir uygulama aç. Uygulamanın ayarlarında "Redirect URI" olarak aşağıdaki adresi ekle; sonra App ID ve App secret key\'i buraya yaz.') }}
                    </p>
                    <div class="mt-3 flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">
                        <code class="min-w-0 flex-1 truncate text-xs text-slate-700" data-redirect-uri>{{ $redirectUri }}</code>
                        <button type="button" class="shrink-0 text-xs font-semibold text-brand-900 hover:underline"
                            onclick="navigator.clipboard.writeText(this.previousElementSibling.textContent); this.textContent = @js(__('Kopyalandı ✓'))">{{ __('Kopyala') }}</button>
                    </div>
                    <form method="POST" action="{{ route('pinterest.app') }}" class="mt-4 space-y-3">
                        @csrf @method('PUT')
                        <x-admin.input name="app_id" :label="__('App ID')" :value="$appId" required autocomplete="off" />
                        <x-admin.input name="app_secret" type="password" :label="__('App secret key')" autocomplete="new-password"
                            :hint="$hasApp ? __('Kayıtlı (şifreli). Değiştirmek istemiyorsan boş bırak.') : __('Şifreli saklanır, bir daha gösterilmez.')" />
                        <button type="submit" class="{{ $btn }}">{{ __('Kaydet') }}</button>
                    </form>
                </div>
            </div>
        </x-admin.card>

        {{-- 2. BAĞLANTI + 3. PANO --}}
        <x-admin.card class="p-6">
            <div class="flex items-start gap-3">
                {!! $step(2, $connected) !!}
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Pinterest hesabı') }}</h2>
                    @if ($connected)
                        <p class="mt-1 text-sm text-emerald-700">{{ $account ? __('Bağlı: @:name', ['name' => $account]) : __('Bağlı') }}</p>
                        <form method="POST" action="{{ route('pinterest.disconnect') }}" class="mt-2" data-confirm="{{ __('Pinterest bağlantısı kaldırılsın mı? Otomatik gönderim durur.') }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">{{ __('Bağlantıyı kaldır') }}</button>
                        </form>
                    @else
                        <p class="mt-1 text-xs text-slate-500">{{ __('Düğmeye basınca Pinterest açılır; hesabına sen girip izin verirsin, sonra buraya dönersin.') }}</p>
                        @if ($hasApp)
                            <a href="{{ route('pinterest.connect') }}" class="mt-3 {{ $btn }} !bg-[#e60023] hover:!bg-[#c8001f]">{{ __('Pinterest\'e bağlan') }}</a>
                        @else
                            <p class="mt-3 text-xs font-medium text-amber-700">{{ __('Önce 1. adımdaki uygulama bilgilerini kaydet.') }}</p>
                        @endif
                    @endif
                </div>
            </div>

            <div class="mt-6 flex items-start gap-3 border-t border-slate-100 pt-6">
                {!! $step(3, $connected && $boardId && $enabled) !!}
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Pano ve günlük sayı') }}</h2>
                    @if (!$connected)
                        <p class="mt-1 text-xs text-slate-500">{{ __('Bağlandıktan sonra panolarını burada seçersin.') }}</p>
                    @elseif ($boardsError)
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $boardsError }}</p>
                    @elseif (!$boards)
                        <p class="mt-1 text-xs font-medium text-amber-700">{{ __('Pinterest hesabında pano yok. Pinterest\'te bir pano aç, sonra bu sayfayı yenile.') }}</p>
                    @else
                        <form method="POST" action="{{ route('pinterest.settings') }}" class="mt-3 space-y-3">
                            @csrf @method('PUT')
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">{{ __('Pano') }}</span>
                                <select name="board_id" required class="{{ $field }}">
                                    <option value="">{{ __('Seç…') }}</option>
                                    @foreach ($boards as $board)
                                        <option value="{{ $board['id'] }}" @selected($boardId === $board['id'])>{{ $board['name'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <x-admin.input name="daily" type="number" :label="__('Günde kaç blog')" :value="$daily" min="1" max="50" required
                                :hint="__('09:00–21:00 arasına yayılarak gönderilir.')" />
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input type="hidden" name="enabled" value="0">
                                <input type="checkbox" name="enabled" value="1" @checked($enabled) class="size-4 accent-brand-900">
                                {{ __('Otomatik gönderim açık') }}
                            </label>
                            <button type="submit" class="{{ $btn }}">{{ __('Kaydet') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </x-admin.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- SIRADAKİLER --}}
        <x-admin.card class="p-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Sıradaki bloglar') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('Yayın tarihine göre eskiden yeniye.') }}</p>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse ($next as $blog)
                    <li class="flex items-center justify-between gap-3 py-2">
                        <a href="{{ route('blogs.edit', $blog) }}" class="min-w-0 truncate font-medium text-slate-800 hover:text-brand-900">{{ $blog->getTranslation('title', 'en', false) }}</a>
                        <span class="shrink-0 text-xs text-slate-400">{{ $blog->published_at?->format('d.m.Y') }}</span>
                    </li>
                @empty
                    <li class="py-2 text-slate-500">{{ __('Sırada blog yok.') }}</li>
                @endforelse
            </ul>
            @if ($failed)
                <form method="POST" action="{{ route('pinterest.retry') }}" class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-red-50 px-3 py-2 ring-1 ring-red-100">
                    @csrf
                    <span class="text-xs font-medium text-red-700">{{ __(':count blog gönderilemedi (sebebi sağdaki listede).', ['count' => $failed]) }}</span>
                    <button type="submit" class="shrink-0 text-xs font-semibold text-red-700 hover:underline">{{ __('Tekrar sıraya al') }}</button>
                </form>
            @endif
        </x-admin.card>

        {{-- SON GÖNDERİLENLER --}}
        <x-admin.card class="p-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Son gönderimler') }}</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse ($recent as $pin)
                    <li class="py-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="min-w-0 truncate font-medium text-slate-800">{{ $pin->blog?->getTranslation('title', 'en', false) ?? '—' }}</span>
                            <span class="shrink-0 text-xs text-slate-400">{{ $pin->created_at->format('d.m H:i') }} · {{ $pin->manual ? __('elle') : __('otomatik') }}</span>
                        </div>
                        @if ($pin->pin_id)
                            <a href="{{ $pin->url() }}" target="_blank" rel="noopener" class="text-xs font-semibold text-emerald-700 hover:underline">{{ __('Pinterest\'te aç') }} ↗</a>
                        @else
                            <p class="text-xs text-red-600">{{ $pin->error }}</p>
                        @endif
                    </li>
                @empty
                    <li class="py-2 text-slate-500">{{ __('Henüz gönderim yok.') }}</li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>
@endsection

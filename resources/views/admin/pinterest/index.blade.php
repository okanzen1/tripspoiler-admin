@extends('layouts.admin')

@section('title', 'Pinterest')

@php
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $btn = 'inline-flex items-center justify-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800';
    $red = 'inline-flex items-center justify-center gap-2 rounded-xl bg-[#e60023] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#c8001f]';
    $boardReady = $connected && ($boardId || $canCreateBoards);
    $ready = $boardReady && $enabled;
    $steps = [
        [__('Uygulama'), $hasApp],
        [__('Hesap'), $connected],
        [__('Pano'), $boardReady],
        [__('Otomatik gönderim'), $ready],
    ];
    $thumb = fn ($blog) => $blog?->coverUrl('sm');
@endphp

@section('header')
    <x-admin.page-header :title="__('Pinterest')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Yayındaki blog yazıları sırayla Pinterest\'e pin olarak gider (İngilizce başlık, kapak resmi ve sitedeki bağlantı). Bir blog yalnızca bir kez gider; blog sayfasından sırayı beklemeden elle de gönderebilirsin.')">
        <x-slot:icon><circle cx="12" cy="12" r="9" /><path d="M10.5 8.5c3-1.5 6 0 5.5 3s-3 4-4.5 3M11.5 10l-2.5 11" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    {{-- gönder / tekrar sıraya al sonrası sayfa yenilenmeden güncellenir (live-refresh.js); ayar formları dışarıda --}}
    <div data-live="pinterest-status">
    {{-- DURUM --}}
    <div class="relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-[#e60023] via-rose-600 to-pink-500 p-6 text-white shadow-[0_24px_50px_-30px_rgba(230,0,35,0.9)] sm:p-8">
        <div class="pointer-events-none absolute -right-16 -top-20 -z-10 size-72 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/70">{{ __('Bugün gönderilen') }}</p>
                <p class="mt-2 text-4xl font-extrabold tracking-tight tabular-nums">{{ $sentToday }} <span class="text-lg font-semibold text-white/80">/ {{ $daily }}</span></p>
                <p class="mt-1 text-sm text-white/85">
                    @if ($ready)
                        {{ $boardId ? __(':board panosuna, 09:00–21:00 arası yaklaşık saatte bir.', ['board' => $boardName]) : __('Her şehrin kendi panosuna, 09:00–21:00 arası yaklaşık saatte bir.') }}
                    @elseif ($connected && !$boardReady)
                        {{ __('Pano açma izni yok: aşağıda "Bağlantıyı kaldır" deyip yeniden bağlan.') }}
                    @elseif ($connected)
                        {{ __('Bağlı; otomatik gönderimi açınca başlar.') }}
                    @else
                        {{ __('Henüz bağlı değil; aşağıdaki adımları tamamla.') }}
                    @endif
                </p>
            </div>
            <ol class="flex flex-wrap gap-2">
                @foreach ($steps as $i => [$label, $done])
                    <li @class(['flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold', 'bg-white text-[#e60023]' => $done, 'bg-white/15 text-white/85 ring-1 ring-white/25' => !$done])>
                        <span @class(['grid size-5 place-items-center rounded-full text-[11px]', 'bg-[#e60023] text-white' => $done, 'bg-white/20' => !$done])>{{ $done ? '✓' : $i + 1 }}</span>{{ $label }}
                    </li>
                @endforeach
            </ol>
        </div>
        <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                [__('Sırada'), $queueCount, __('yayında, kapaklı')],
                [__('Gönderilen'), $sentBlogs, __('blog')],
                [__('Gönderilemeyen'), $failing->count(), __('aşağıda listede')],
                [__('Kapak resmi yok'), $noCover, __('resim eklenince sıraya girer')],
            ] as [$label, $value, $hint])
                <div class="rounded-2xl bg-white/12 px-4 py-3 ring-1 ring-white/20 backdrop-blur-sm">
                    <dt class="text-xs font-semibold text-white/75">{{ $label }}</dt>
                    <dd class="mt-0.5 text-2xl font-extrabold tabular-nums">{{ $value }}</dd>
                    <dd class="text-[11px] text-white/70">{{ $hint }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    @if ($trialBlocked)
        <div class="mt-6 rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-200">
            <p class="text-sm font-semibold text-amber-900">{{ __('Pinterest onayı bekleniyor: uygulama "Trial" erişimde') }}</p>
            <p class="mt-1 text-sm text-amber-800">{{ __('Pinterest bu seviyede gerçek pin açtırmıyor. developers.pinterest.com › Uygulamalarım › uygulaman › "Erişimi yükseltin" ile Standard erişim iste. Otomatik gönderim durduruldu; bloglar deneme hakkı harcamadan sırada bekliyor. Onay gelince aşağıda "Otomatik gönderim açık"ı işaretleyip kaydet.') }}</p>
        </div>
    @endif

    {{-- GÖNDERİLEMEYENLER --}}
    @if ($failing->isNotEmpty())
        <x-admin.card class="mt-6 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-red-50/60 px-6 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-red-800">{{ __('Gönderilemeyenler (:count)', ['count' => $failing->count()]) }}</h2>
                    <p class="text-xs text-red-700/80">{{ __(':hours saat sonra kendiliğinden yeniden denenir (en fazla :max kez). İstersen hemen gönder.', ['hours' => $retryHours, 'max' => $maxTries]) }}</p>
                </div>
                <form method="POST" action="{{ route('pinterest.retry') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-white px-3.5 py-2 text-sm font-semibold text-red-700 ring-1 ring-red-200 transition hover:bg-red-50">{{ __('Hepsini yeniden sıraya al') }}</button>
                </form>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($failing as $blog)
                    @php
                        $last = $blog->pinterestPins->first();
                    @endphp
                    <li class="flex flex-wrap items-center gap-4 px-6 py-3.5">
                        @if ($url = $thumb($blog))
                            <img src="{{ $url }}" alt="" loading="lazy" class="h-12 w-16 shrink-0 rounded-lg object-cover ring-1 ring-slate-200" />
                        @else
                            <span class="grid h-12 w-16 shrink-0 place-items-center rounded-lg bg-slate-100 text-[10px] font-semibold text-slate-400">{{ __('Resim yok') }}</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('blogs.edit', $blog) }}" class="line-clamp-1 text-sm font-semibold text-slate-900 hover:text-brand-900 hover:underline">{{ $blog->getTranslation('title', 'en', false) }}</a>
                            <p class="mt-0.5 line-clamp-2 text-xs text-red-600">{{ $last?->error }}</p>
                            <p class="text-[11px] text-slate-400">{{ __(':count deneme · son: :date', ['count' => $blog->pinterest_pins_count, 'date' => $last?->created_at->format('d.m H:i')]) }}</p>
                        </div>
                        @if ($blog->cover_path && $connected)
                            <form method="POST" action="{{ route('pinterest.send', $blog) }}">
                                @csrf
                                <button type="submit" class="{{ $red }} !px-3.5 !py-2">{{ __('Şimdi gönder') }}</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- SIRADAKİLER --}}
        <x-admin.card class="overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Sıradaki bloglar') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Yayın tarihine göre eskiden yeniye.') }}</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-slate-600">{{ $queueCount }}</span>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($next as $blog)
                    <li class="flex items-center gap-3 px-6 py-3">
                        <img src="{{ $thumb($blog) }}" alt="" loading="lazy" class="h-11 w-14 shrink-0 rounded-lg object-cover ring-1 ring-slate-200" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('blogs.edit', $blog) }}" class="line-clamp-1 text-sm font-medium text-slate-800 hover:text-brand-900 hover:underline">{{ $blog->getTranslation('title', 'en', false) }}</a>
                            <p class="text-[11px] text-slate-400">{{ $blog->published_at?->format('d.m.Y') }}</p>
                        </div>
                        @if ($connected)
                            <form method="POST" action="{{ route('pinterest.send', $blog) }}">
                                @csrf
                                <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#e60023] ring-1 ring-[#e60023]/25 transition hover:bg-red-50">{{ __('Şimdi gönder') }}</button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-slate-500">{{ $noCover ? __('Sırada blog yok. :count yayındaki blogun kapak resmi eksik.', ['count' => $noCover]) : __('Sırada blog yok.') }}</li>
                @endforelse
            </ul>
        </x-admin.card>

        {{-- GÖNDERİLENLER --}}
        <x-admin.card class="overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Gönderilenler') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Bir kez giden blog tekrar gitmez.') }}</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold tabular-nums text-emerald-700">{{ $sentBlogs }}</span>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($sent as $pin)
                    <li class="flex items-center gap-3 px-6 py-3">
                        @if ($url = $thumb($pin->blog))
                            <img src="{{ $url }}" alt="" loading="lazy" class="h-11 w-14 shrink-0 rounded-lg object-cover ring-1 ring-slate-200" />
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-1 text-sm font-medium text-slate-800">{{ $pin->blog?->getTranslation('title', 'en', false) ?? '—' }}</p>
                            <p class="text-[11px] text-slate-400">{{ $pin->created_at->format('d.m.Y H:i') }} · {{ $pin->manual ? __('elle') : __('otomatik') }}</p>
                        </div>
                        <a href="{{ $pin->url() }}" target="_blank" rel="noopener" class="shrink-0 text-xs font-semibold text-emerald-700 hover:underline">{{ __('Pini aç') }} ↗</a>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-slate-500">{{ __('Henüz gönderim yok.') }}</li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>

    @if ($boardMap && !$boardId)
        <x-admin.card class="mt-6 p-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Panolar (:count)', ['count' => count($boardMap)]) }}</h2>
            <p class="text-xs text-slate-500">{{ __('Her şehrin blogları kendi panosuna gider.') }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (collect($boardMap)->sortBy('name') as $board)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-[#e60023] ring-1 ring-red-100">
                        <span class="size-1.5 rounded-full bg-[#e60023]"></span>{{ $board['name'] }}
                    </span>
                @endforeach
            </div>
        </x-admin.card>
    @endif

    </div>

    {{-- AYARLAR --}}
    <h2 class="mt-10 text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('Ayarlar') }}</h2>
    <div class="mt-3 grid gap-6 lg:grid-cols-3">
        {{-- 1. UYGULAMA --}}
        <x-admin.card class="p-6">
            <p class="text-xs font-bold text-slate-400">1</p>
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Pinterest uygulaması') }}</h3>
            <p class="mt-1 text-xs leading-relaxed text-slate-500">
                {{ __('developers.pinterest.com › My apps › Connect app ile bir uygulama aç. Uygulamanın ayarlarında "Redirect URI" olarak aşağıdaki adresi ekle; sonra App ID ve App secret key\'i buraya yaz.') }}
            </p>
            <div class="mt-3 flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">
                <code class="min-w-0 flex-1 truncate text-xs text-slate-700">{{ $redirectUri }}</code>
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
        </x-admin.card>

        {{-- 2. HESAP --}}
        <x-admin.card class="p-6">
            <p class="text-xs font-bold text-slate-400">2</p>
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Pinterest hesabı') }}</h3>
            @if ($connected)
                <p class="mt-3 inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">
                    <span class="size-2 rounded-full bg-emerald-500"></span>{{ $account ? __('Bağlı: @:name', ['name' => $account]) : __('Bağlı') }}
                </p>
                <form method="POST" action="{{ route('pinterest.disconnect') }}" class="mt-3" data-confirm="{{ __('Pinterest bağlantısı kaldırılsın mı? Otomatik gönderim durur.') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">{{ __('Bağlantıyı kaldır') }}</button>
                </form>
            @else
                <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ __('Düğmeye basınca Pinterest açılır; hesabına sen girip izin verirsin, sonra buraya dönersin.') }}</p>
                @if ($hasApp)
                    <a href="{{ route('pinterest.connect') }}" class="mt-4 {{ $red }}">{{ __('Pinterest\'e bağlan') }}</a>
                @else
                    <p class="mt-3 text-xs font-medium text-amber-700">{{ __('Önce 1. adımdaki uygulama bilgilerini kaydet.') }}</p>
                @endif
            @endif
        </x-admin.card>

        {{-- 3. PANO --}}
        <x-admin.card class="p-6">
            <p class="text-xs font-bold text-slate-400">3</p>
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Pano ve günlük sayı') }}</h3>
            @if ($connected && !$boardReady)
                <p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 ring-1 ring-amber-200">{{ __('Pano açma izni yok: 2. kutuda "Bağlantıyı kaldır" deyip yeniden bağlan; pano otomatik açılır.') }}</p>
            @endif
            @if (!$connected)
                <p class="mt-1 text-xs text-slate-500">{{ __('Bağlandıktan sonra panolarını burada seçersin.') }}</p>
            @elseif ($boardsError)
                <p class="mt-2 text-xs font-medium text-red-600">{{ $boardsError }}</p>
            @else
                <form method="POST" action="{{ route('pinterest.settings') }}" class="mt-3 space-y-3">
                    @csrf @method('PUT')
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">{{ __('Pano') }}</span>
                        <select name="board_id" class="{{ $field }}">
                            <option value="">{{ __('Otomatik: her şehre kendi panosu') }}</option>
                            @foreach ($boards as $board)
                                <option value="{{ $board['id'] }}" @selected($boardId === $board['id'])>{{ $board['name'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <p class="-mt-1 text-[11px] text-slate-500">{{ __('Otomatikte "Rome Travel Guide" gibi şehir panoları kullanılır; yoksa Pinterest\'te açılır. Şehri olmayan yazılar ":name" panosuna gider. Bir pano seçersen hepsi o panoya gider.', ['name' => $autoBoard]) }}</p>
                    <x-admin.input name="daily" type="number" :label="__('Günde kaç blog')" :value="$daily" min="1" max="50" required
                        :hint="__('09:00–21:00 arasına yayılarak gönderilir.')" />
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" @checked($enabled) class="size-4 accent-[#e60023]">
                        {{ __('Otomatik gönderim açık') }}
                    </label>
                    <button type="submit" class="{{ $btn }}">{{ __('Kaydet') }}</button>
                </form>
            @endif
        </x-admin.card>
    </div>
@endsection

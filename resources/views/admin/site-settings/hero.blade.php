@extends('layouts.admin')

@section('title', 'Hero alanı')

@php
    $featured = array_map('intval', (array) old('hero_featured', $hero['featured']));
    $passId = (int) old('hero_pass_id', $hero['pass_id']);
    $guideId = (int) old('hero_guide_id', $hero['guide_id']);
    $max = \App\Support\HeroSettings::FEATURED_MAX;
    $passes = $activities->filter(fn ($activity) => $activity->type?->value === 'pass');
    // Sitede görünmeyecekse nedeni (seçilemez)
    $hiddenReason = fn ($activity) => !$activity->active ? __('Pasif') : (!$activity->cover ? __('Resmi yok') : null);
    $overlays = [
        'soft' => ['Az', 'Fotoğraf daha canlı. Açık renkli fotoğrafta yazı zor okunabilir.'],
        'medium' => ['Orta (önerilen)', 'Hem fotoğraf hem yazılar net.'],
        'strong' => ['Çok', 'Çok parlak ya da renkli fotoğrafta yazıyı öne çıkarır.'],
    ];
    // Adım numarası rozeti
    $badge = fn ($n, $color) => '<span class="grid size-8 shrink-0 place-items-center rounded-full text-sm font-bold text-white ' . $color . '">' . $n . '</span>';
@endphp

@section('header')
    <x-admin.page-header :title="__('Hero alanı')" :breadcrumb="['Yönetim' => route('management.index'), 'Sayfalar' => route('pages.index'), 'Anasayfa' => route('pages.edit', \App\Models\Page::where('key', 'home')->value('id') ?? 1)]"
        :description="__('Anasayfayı açınca ilk görülen büyük alan. Aşağıdaki 5 adımı sırayla değiştirebilirsin; istemediğin adımı boş bırak.')">
        <x-slot:icon><rect x="3" y="4" width="18" height="16" rx="2" /><path d="m3 16 5-5 4 4 3-3 6 6" /><circle cx="15.5" cy="8.5" r="1.5" /></x-slot:icon>
        <a href="{{ config('site.url') }}" target="_blank" rel="noopener"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">
            {{ __('Anasayfayı aç') }} ↗
        </a>
    </x-admin.page-header>
@endsection

@section('footer-action')
    {{-- Footer'daki Kaydet: formun dışında olduğu için form="hero-settings" ile bağlı --}}
    <button type="submit" form="hero-settings"
        class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-900/25">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ __('Değişiklikleri kaydet') }}
    </button>
@endsection

@section('content')
    <form action="{{ route('site-settings.hero.update') }}" method="POST" enctype="multipart/form-data" id="hero-settings" class="space-y-6">
        @csrf
        @method('PUT')

        <div data-form-errors class="rounded-2xl bg-red-50 px-5 py-4 text-sm font-medium text-red-700 ring-1 ring-red-200 @if (!$errors->any()) hidden @endif">
            <p class="font-semibold">{{ __('Kaydedilemedi. Lütfen şu hataları düzelt:') }}</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>

        {{-- HARİTA: hangi numara nerede --}}
        <x-admin.card class="p-6 sm:p-8">
            <p class="text-lg font-semibold text-slate-900">{{ __('Anasayfada nereye denk geliyor?') }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ __('Resimdeki numaralar aşağıdaki adımlarla aynı. Bir numaraya tıklarsan o adıma gider.') }}</p>

            <div class="mt-5 grid gap-6 lg:grid-cols-5 lg:items-center">
                <div class="relative aspect-[16/9] overflow-hidden rounded-2xl bg-slate-900 lg:col-span-3">
                    <img src="{{ $hero['image_url'] }}" alt="" data-preview="map" @class(['absolute inset-0 size-full object-cover', 'hidden' => !$hero['image_url']]) style="object-position: {{ $hero['focus'] }}% center">
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/85 via-slate-950/40 to-transparent"></div>
                    {{-- menü --}}
                    <div class="absolute inset-x-0 top-0 flex h-[9%] items-center justify-between border-b border-white/10 px-[4%]">
                        <div class="h-2 w-[12%] rounded bg-white/70"></div>
                        <div class="h-2 w-[30%] rounded bg-white/30"></div>
                        <div class="h-3 w-[12%] rounded-full bg-red-500"></div>
                    </div>
                    {{-- başlık + arama --}}
                    <div class="absolute left-[6%] top-[20%] w-[46%] space-y-[3%]">
                        <div class="h-3 w-full rounded bg-white/90"></div>
                        <div class="h-3 w-4/5 rounded bg-white/90"></div>
                        <div class="h-3 w-3/5 rounded bg-red-500"></div>
                        <div class="!mt-[6%] h-4 w-full rounded bg-white"></div>
                    </div>
                    {{-- 2: yazılar --}}
                    <a href="#adim-2" class="absolute left-[4%] top-[16%] grid h-[30%] w-[50%] place-items-start rounded-lg border-2 border-dashed border-violet-300 transition hover:bg-white/10">
                        <span class="-ml-3 -mt-3 grid size-8 place-items-center rounded-full bg-violet-500 text-sm font-bold text-white shadow-lg">2</span>
                    </a>
                    {{-- 1: arka plan --}}
                    <a href="#adim-1" class="absolute left-[56%] top-[13%] grid size-8 place-items-center rounded-full bg-rose-500 text-sm font-bold text-white shadow-lg ring-4 ring-rose-500/30 transition hover:scale-110">1</a>
                    {{-- 2: kayan kart --}}
                    <a href="#adim-3" class="absolute right-[6%] top-[18%] grid h-[44%] w-[28%] place-items-center rounded-xl border-2 border-dashed border-amber-300 bg-white/15 backdrop-blur transition hover:bg-white/25">
                        <span class="grid size-8 place-items-center rounded-full bg-amber-500 text-sm font-bold text-white shadow-lg">3</span>
                    </a>
                    {{-- 3 ve 4: beyaz kartlar --}}
                    <a href="#adim-4" class="absolute bottom-[12%] left-[6%] grid h-[18%] w-[43%] place-items-center rounded-lg border-2 border-sky-400 bg-white transition hover:scale-[1.02]">
                        <span class="grid size-8 place-items-center rounded-full bg-sky-500 text-sm font-bold text-white">4</span>
                    </a>
                    <a href="#adim-5" class="absolute bottom-[12%] right-[6%] grid h-[18%] w-[43%] place-items-center rounded-lg border-2 border-emerald-400 bg-white transition hover:scale-[1.02]">
                        <span class="grid size-8 place-items-center rounded-full bg-emerald-500 text-sm font-bold text-white">5</span>
                    </a>
                </div>

                <ol class="space-y-3 text-sm lg:col-span-2">
                    @foreach ([
                        [1, 'bg-rose-500', 'Arka plan resmi', 'Her şeyin arkasındaki büyük fotoğraf.'],
                        [2, 'bg-violet-500', 'Yazılar', 'Büyük başlık, üstündeki küçük yazı ve açıklama.'],
                        [3, 'bg-amber-500', 'Sağdaki kayan kart', 'Seçtiğin aktiviteler sırayla değişir.'],
                        [4, 'bg-sky-500', 'Sol beyaz kart', 'Öne çıkardığın şehir kartı (pass).'],
                        [5, 'bg-emerald-500', 'Sağ beyaz kart', 'Öne çıkardığın blog yazısı.'],
                    ] as [$n, $color, $title, $text])
                        <li>
                            <a href="#adim-{{ $n }}" class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-slate-50">
                                {!! $badge($n, $color) !!}
                                <span><span class="font-semibold text-slate-900">{{ __($title) }}</span><br><span class="text-slate-500">{{ __($text) }}</span></span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        </x-admin.card>

        {{-- ADIM 1: ARKA PLAN RESMİ --}}
        <x-admin.card class="scroll-mt-20 p-6 sm:p-8" id="adim-1">
            <div class="flex items-start gap-3">
                {!! $badge(1, 'bg-rose-500') !!}
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ __('Arka plan resmi') }}</p>
                    <p class="text-sm text-slate-600">{{ __('Anasayfanın en üstünde, yazıların arkasında duran büyük fotoğraf.') }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-5">
                <div class="lg:col-span-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Şu anki resim') }}</p>
                    <div class="relative aspect-[16/9] overflow-hidden rounded-2xl bg-slate-900">
                        <img src="{{ $hero['image_url'] }}" alt="" data-preview="desktop" @class(['absolute inset-0 size-full object-cover', 'hidden' => !$hero['image_url']]) style="object-position: {{ $hero['focus'] }}% center">
                        <div class="absolute inset-0" data-preview-overlay></div>
                        <div class="absolute left-[6%] top-[25%] w-[46%] space-y-[3%]">
                            <div class="h-3 w-full rounded bg-white/90"></div>
                            <div class="h-3 w-4/5 rounded bg-white/90"></div>
                            <div class="h-3 w-3/5 rounded bg-red-500"></div>
                        </div>
                        <span class="absolute inset-0 grid place-items-center text-sm text-white/60" data-preview-placeholder="desktop" @class(['hidden' => $hero['image_url']])>{{ __('Henüz resim yüklenmedi') }}</span>
                    </div>
                </div>

                <div class="space-y-4 lg:col-span-2">
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700 ring-1 ring-slate-200">
                        <p class="font-semibold text-slate-900">{{ __('Nasıl bir resim seçmeliyim?') }}</p>
                        <ul class="mt-2 space-y-2">
                            <li class="flex gap-2"><span class="text-emerald-600">✓</span>{{ __('Yatay bir fotoğraf (telefonla dik çekilmiş olmasın).') }}</li>
                            <li class="flex gap-2"><span class="text-emerald-600">✓</span>{{ __('Fotoğrafın güzel kısmı sağ tarafta olsun; sol tarafa yazılar gelecek.') }}</li>
                            <li class="flex gap-2"><span class="text-emerald-600">✓</span>{{ __('Üzerinde yazı ya da logo olmasın.') }}</li>
                            <li class="flex gap-2"><span class="text-emerald-600">✓</span>{{ __('Ne kadar büyükse o kadar net (en iyisi 2560 piksel genişlik).') }}</li>
                            <li class="flex gap-2"><span class="text-emerald-600">✓</span>{{ __('Dosya boyutunu dert etme; büyükse otomatik küçültülür.') }}</li>
                        </ul>
                    </div>

                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-brand-900 px-5 py-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                        <span data-file-label="hero_image">{{ $hero['image'] ? __('Resmi değiştir') : __('Resim seç') }}</span>
                        <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" class="sr-only" data-hero-file="desktop">
                    </label>
                    <p class="hidden rounded-xl px-3 py-2 text-xs font-medium ring-1" data-file-info="desktop"></p>
                    @error('hero_image') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- TELEFON --}}
                    <div class="mt-6 flex flex-col gap-5 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200 sm:flex-row">
                        <div class="relative h-56 w-[7.5rem] shrink-0 overflow-hidden rounded-[1.4rem] border-4 border-slate-900 bg-slate-900">
                            <img src="{{ $hero['image_mobile_url'] ?: $hero['image_url'] }}" alt="" data-preview="mobile" @class(['absolute inset-0 size-full object-cover', 'hidden' => !($hero['image_mobile_url'] ?: $hero['image_url'])])
                                style="object-position: {{ $hero['image_mobile_url'] ? '50' : $hero['focus'] }}% center">
                            <div class="absolute inset-0" data-preview-overlay></div>
                            <div class="absolute left-2.5 right-2.5 top-10 space-y-1">
                                <div class="h-2 w-full rounded bg-white/95"></div>
                                <div class="h-2 w-4/5 rounded bg-white/95"></div>
                                <div class="h-2 w-3/5 rounded bg-red-500"></div>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm text-slate-600">
                            <p class="font-semibold text-slate-900">📱 {{ __('Telefonda nasıl görünüyor?') }}</p>
                            <p>{{ __('Telefon ekranı dik olduğu için yatay resmin sadece bir dilimi görünür (soldaki gibi). Beğenmezsen telefon için ayrı, dik bir resim yükleyebilirsin.') }}</p>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" /></svg>
                                    {{ __($hero['image_mobile'] ? 'Telefon resmini değiştir' : 'Telefon için dik resim seç') }}
                                    <input type="file" name="hero_image_mobile" accept="image/jpeg,image/png,image/webp" class="sr-only" data-hero-file="mobile">
                                </label>
                                @if ($hero['image_mobile'])
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:bg-red-50 hover:text-red-600">
                                        <input type="checkbox" name="remove_hero_image_mobile" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                        {{ __('Telefon resmini kaldır') }}
                                    </label>
                                @endif
                            </div>
                            <p class="hidden text-xs font-medium" data-file-info="mobile"></p>
                            @error('hero_image_mobile') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

            {{-- İSTEĞE BAĞLI --}}
            <details class="group mt-6 rounded-2xl ring-1 ring-slate-200 open:bg-slate-50/50" @if ($errors->hasAny(['hero_focus', 'hero_overlay'])) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-sm font-semibold text-slate-800">
                    <span>{{ __('İsteğe bağlı ayarlar') }} <span class="font-normal text-slate-500">— {{ __('kadraj ve karartma. Bilmiyorsan dokunma, şu haliyle iyi.') }}</span></span>
                    <svg class="size-4 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M6 9l6 6 6-6" /></svg>
                </summary>

                <div class="space-y-8 border-t border-slate-200 px-5 py-6">

                    {{-- Kadraj --}}
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ __('Kadraj: resmin hangi tarafı görünsün?') }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ __('Ekran dar olduğunda resmin bir kısmı kesilir. Kaydırarak hangi tarafın görüneceğini seç; yukarıdaki resimlerde hemen görürsün.') }}</p>
                        <div class="mt-3 flex items-center gap-4">
                            <span class="text-xs font-medium text-slate-500">{{ __('Sol taraf') }}</span>
                            <input type="range" name="hero_focus" min="0" max="100" step="5" value="{{ old('hero_focus', $hero['focus']) }}" class="w-full accent-brand-900" data-hero-focus>
                            <span class="text-xs font-medium text-slate-500">{{ __('Sağ taraf') }}</span>
                        </div>
                    </div>

                    {{-- Karartma --}}
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ __('Karartma: yazılar okunabilsin diye resim ne kadar koyulaşsın?') }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            @foreach ($overlays as $value => [$label, $description])
                                <label class="relative flex cursor-pointer flex-col rounded-2xl bg-white p-4 ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:ring-2 has-[:checked]:ring-brand-900">
                                    <input type="radio" name="hero_overlay" value="{{ $value }}" class="sr-only" data-hero-overlay @checked(old('hero_overlay', $hero['overlay']) === $value)>
                                    <span class="text-sm font-semibold text-slate-900">{{ __($label) }}</span>
                                    <span class="mt-1 text-xs leading-relaxed text-slate-500">{{ __($description) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </details>
        </x-admin.card>

        {{-- ADIM 2: YAZILAR (çeviri butonu formun dışındaki "hero-translate" formuna bağlı) --}}
        @php
            $textFields = [
                'eyebrow' => ['Üstteki küçük yazı', 'Başlığın üstünde, kırmızı çizginin yanındaki küçük yazı.', 60],
                'title_1' => ['Başlık 1. satır', null, 40],
                'title_2' => ['Başlık 2. satır', 'İsteğe bağlı.', 40],
                'title_3' => ['Başlık 3. satır', 'İsteğe bağlı. 2 satırlık başlık için boş bırak.', 40],
                'title_highlight' => ['Kırmızı kelime', 'Başlığın en son dolu satırının sonuna kırmızıyla eklenir.', 40],
                'subtitle' => ['Alt başlık', 'Başlığın altındaki kalın kısa satır. İsteğe bağlı.', 80],
                'description' => ['Açıklama', 'En alttaki bir iki cümlelik yazı.', 260],
            ];
            $textStatus = app(\App\Services\Translation\ModelTranslator::class)->status($heroText);
            $textPending = count(array_filter($textStatus, fn ($state) => $state !== 'ok'));
        @endphp
        <x-admin.card class="scroll-mt-20 p-6 sm:p-8" id="adim-2">
            <div class="flex items-start gap-3">
                {!! $badge(2, 'bg-violet-500') !!}
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ __('Yazılar') }}</p>
                    <p class="text-sm text-slate-600">{{ __('İngilizce yaz ve kaydet. Sonra aşağıdaki "Diğer dillere çevir" butonuyla 12 dile çevrilir.') }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-5">
                <div class="space-y-4 lg:col-span-3">
                    @foreach ($textFields as $field => [$label, $hint, $limit])
                        <label class="block">
                            <span class="flex items-center justify-between text-sm font-medium text-slate-700">
                                <span>{{ __($label) }}@if ($field === 'title_1') <span class="text-red-500" title="{{ __('Zorunlu') }}">*</span>@endif</span>
                                @error("text.$field") <span class="text-xs font-medium text-red-600">{{ $message }}</span> @enderror
                            </span>
                            @if ($field === 'description')
                                <textarea name="text[{{ $field }}]" rows="3" maxlength="{{ $limit }}" data-hero-text="{{ $field }}"
                                    class="mt-1.5 block w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-relaxed outline-none transition focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10">{{ old("text.$field", $heroText->getTranslation($field, 'en', false)) }}</textarea>
                            @else
                                <input type="text" name="text[{{ $field }}]" maxlength="{{ $limit }}" data-hero-text="{{ $field }}" autocomplete="off"
                                    value="{{ old("text.$field", $heroText->getTranslation($field, 'en', false)) }}"
                                    @if ($field === 'title_1') required @endif
                                    class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10 @error("text.$field") !border-red-400 @enderror">
                            @endif
                            @if ($hint) <span class="mt-1 block text-xs text-slate-500">{{ __($hint) }}</span> @endif
                        </label>
                    @endforeach
                </div>

                {{-- Canlı önizleme --}}
                <div class="lg:col-span-2">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Önizleme') }}</p>
                    <div class="relative overflow-hidden rounded-2xl bg-slate-900 p-6 text-white lg:sticky lg:top-20">
                        @if ($hero['image_url'])
                            <img src="{{ $hero['image_url'] }}" alt="" class="absolute inset-0 size-full object-cover opacity-40" style="object-position: {{ $hero['focus'] }}% center">
                        @endif
                        <div class="relative">
                            <p class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] text-white/75"><span class="h-px w-5 bg-red-500"></span><span data-preview-text="eyebrow"></span></p>
                            <p class="mt-3 text-3xl font-extrabold leading-[1] tracking-tight" data-preview-title></p>
                            <p class="mt-3 text-base font-semibold text-white" data-preview-text="subtitle"></p>
                            <p class="mt-2 text-sm leading-relaxed text-white/85" data-preview-text="description"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Çeviriler --}}
            <div class="mt-6 rounded-2xl ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ __('Diğer diller') }} <span class="font-normal text-slate-500">· {{ __(':ok / :total dil güncel', ['ok' => count($textStatus) - $textPending, 'total' => count($textStatus)]) }}</span></p>
                        <p class="text-xs text-slate-500">{{ __('İngilizceyi değiştirdiysen önce "Değişiklikleri kaydet"e bas, sonra çevir. Bir dile tıklarsan o dildeki yazıyı elle düzeltebilirsin.') }}</p>
                    </div>
                    @if ($textPending)
                        <button type="submit" form="hero-translate" class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6" /></svg>
                            {{ __('Diğer dillere çevir (:count)', ['count' => $textPending]) }}
                        </button>
                    @else
                        <span class="rounded-xl bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-700">✓ {{ __('Tüm diller güncel') }}</span>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2 border-t border-slate-100 px-5 py-4">
                    @foreach (\App\Models\Language::targets() as $language)
                        @php $state = $textStatus[$language->code] ?? 'missing'; @endphp
                        <a href="{{ route('translations.edit', ['hero', $heroText->id, $language->code]) }}"
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium ring-1 ring-slate-200 transition hover:bg-slate-50"
                            title="{{ ['ok' => __('Güncel'), 'stale' => __('İngilizce değişti, yeniden çevir')][$state] ?? __('Çevrilmedi') }}">
                            <span class="size-2 rounded-full {{ ['ok' => 'bg-emerald-500', 'stale' => 'bg-amber-500'][$state] ?? 'bg-slate-300' }}"></span>
                            {{ $language->native_name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </x-admin.card>

        {{--
            3-5. adımlar: aktivite/blog sayısı arttıkça sayfa uzamasın diye her liste aranabilir, şehre göre
            süzülebilir ve kendi içinde kayar. Sitede görünmeyenler (pasif, resimsiz) varsayılan olarak gizli.
        --}}
        @php
            $typeLabel = fn ($activity) => $activity->type?->label();
            $cityOf = fn ($model) => $model->city?->name ?? __('Şehirsiz');
            // Arama tüm dillerdeki adlara bakar ("ayasofya" da "hagia sophia" da bulur)
            $keywords = fn ($model, $field) => implode(' ', array_filter((array) $model->getTranslations($field)));
            $filterBar = function (string $placeholder, $cities) {
                return view('admin.site-settings._filter-bar', compact('placeholder', 'cities'))->render();
            };
        @endphp

        {{-- ADIM 2: KAYAN KART --}}
        <x-admin.card class="scroll-mt-20 p-6 sm:p-8" id="adim-3" data-featured data-max="{{ $max }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    {!! $badge(3, 'bg-amber-500') !!}
                    <div>
                        <p class="text-lg font-semibold text-slate-900">{{ __('Sağdaki kayan kart') }}</p>
                        <p class="text-sm text-slate-600">{{ __('Aşağıdaki listeden en fazla :max aktivite ekle. Kartta eklediğin sırayla, birkaç saniyede bir değişerek gösterilir.', ['max' => $max]) }}</p>
                    </div>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-800 ring-1 ring-amber-200" data-featured-count></span>
            </div>

            {{-- SEÇTİKLERİN --}}
            <div class="mt-5 rounded-2xl bg-amber-50/50 p-4 ring-1 ring-amber-200/70">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Kartta gösterilecekler') }}</p>
                    <button type="button" class="text-xs font-semibold text-slate-500 hover:text-red-600" data-featured-clear>{{ __('Hepsini çıkar') }}</button>
                </div>
                <p class="mt-2 text-sm text-amber-900" data-featured-empty>
                    {{ __('Henüz bir şey eklemedin. Böyle bırakırsan Aktiviteler sayfasında "popüler" yaptığın aktiviteler otomatik gösterilir.') }}
                </p>
                <ol class="mt-3 space-y-2" data-featured-tray></ol>
            </div>

            {{-- Seçim sırası formda bu gizli alanlarla gider --}}
            <div data-featured-inputs>
                @foreach ($featured as $id)
                    <input type="hidden" name="hero_featured[]" value="{{ $id }}">
                @endforeach
            </div>

            {{-- LİSTE --}}
            <div class="mt-5" data-filter>
                {!! $filterBar(__('Aktivite ara… (ör. Ayasofya, Boğaz)'), $activities->map($cityOf)->unique()->values()) !!}
                <div class="mt-3 max-h-[26rem] divide-y divide-slate-100 overflow-y-auto rounded-2xl ring-1 ring-slate-200" data-filter-list>
                    @foreach ($activities as $activity)
                        @php $reason = $hiddenReason($activity); @endphp
                        <div class="flex items-center gap-3 px-3 py-2.5" data-filter-item data-name="{{ $activity->name }}" data-keywords="{{ $keywords($activity, 'name') }}" data-city="{{ $cityOf($activity) }}" @if ($reason) data-unavailable @endif
                            data-featured-row="{{ $activity->id }}" data-thumb="{{ $activity->cover?->url('sm') }}">
                            <span class="h-10 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                @if ($activity->cover) <img src="{{ $activity->cover->url('sm') }}" alt="" loading="lazy" class="size-full object-cover"> @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-slate-900" title="{{ $activity->name }}">{{ $activity->name }}</span>
                                <span class="block truncate text-xs text-slate-500">
                                    {{ $cityOf($activity) }} · {{ $typeLabel($activity) }}@if ($reason) · <span class="font-semibold text-red-600">{{ $reason }} — {{ __('sitede görünmez') }}</span>@endif
                                </span>
                            </span>
                            @unless ($reason)
                                <button type="button" data-featured-toggle="{{ $activity->id }}"
                                    class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 transition disabled:cursor-not-allowed disabled:opacity-40"></button>
                            @endunless
                        </div>
                    @endforeach
                    <p class="hidden px-4 py-8 text-center text-sm text-slate-500" data-filter-empty>{{ __('Aramana uyan aktivite yok.') }}</p>
                </div>
                @include('admin.site-settings._filter-footer', ['unit' => __('aktivite')])
            </div>
            @error('hero_featured') <p class="mt-3 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            @error('hero_featured.*') <p class="mt-3 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </x-admin.card>

        {{-- ADIM 3: ŞEHİR KARTI --}}
        <x-admin.card class="scroll-mt-20 p-6 sm:p-8" id="adim-4" data-single="hero_pass_id">
            <div class="flex items-start gap-3">
                {!! $badge(4, 'bg-sky-500') !!}
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ __('Sol beyaz kart: hangi şehir kartı öne çıksın?') }}</p>
                    <p class="text-sm text-slate-600">{{ __('Listeden birini seç. Kartta adı ve o şehirde kaç şehir kartı olduğu yazar.') }}</p>
                </div>
            </div>
            <p class="mt-5 rounded-2xl bg-sky-50 px-4 py-3 text-sm text-sky-900 ring-1 ring-sky-200/70">
                {{ __('Şu an seçili:') }} <strong data-single-current></strong>
            </p>
            <div class="mt-4" data-filter>
                {!! $filterBar(__('Şehir kartı ara…'), $passes->map($cityOf)->unique()->values()) !!}
                <div class="mt-3 max-h-[22rem] divide-y divide-slate-100 overflow-y-auto rounded-2xl ring-1 ring-slate-200" data-filter-list>
                    <label class="flex cursor-pointer items-center gap-3 px-3 py-2.5 transition hover:bg-slate-50 has-[:checked]:bg-sky-50" data-filter-pinned>
                        <input type="radio" name="hero_pass_id" value="" class="size-4 border-slate-300 text-sky-600 focus:ring-sky-500" @checked(!$passId) data-label="{{ __('Otomatik (sıralamadaki ilk şehir kartı)') }}">
                        <span class="grid h-10 w-14 shrink-0 place-items-center rounded-lg bg-sky-100">✨</span>
                        <span><span class="block text-sm font-semibold text-slate-900">{{ __('Otomatik') }}</span><span class="text-xs text-slate-500">{{ __('Aktivite sıralamasındaki ilk şehir kartı') }}</span></span>
                    </label>
                    @foreach ($passes as $activity)
                        <label @class(['flex items-center gap-3 px-3 py-2.5 transition has-[:checked]:bg-sky-50', 'cursor-pointer hover:bg-slate-50' => $activity->active, 'cursor-not-allowed opacity-50' => !$activity->active])
                            data-filter-item data-name="{{ $activity->name }}" data-keywords="{{ $keywords($activity, 'name') }}" data-city="{{ $cityOf($activity) }}" @unless ($activity->active) data-unavailable @endunless>
                            <input type="radio" name="hero_pass_id" value="{{ $activity->id }}" class="size-4 border-slate-300 text-sky-600 focus:ring-sky-500" @checked($passId === $activity->id) @disabled(!$activity->active) data-label="{{ $activity->name }}">
                            <span class="h-10 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                @if ($activity->cover) <img src="{{ $activity->cover->url('sm') }}" alt="" loading="lazy" class="size-full object-cover"> @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-slate-900" title="{{ $activity->name }}">{{ $activity->name }}</span>
                                <span class="text-xs text-slate-500">{{ $cityOf($activity) }}@unless ($activity->active) · <span class="font-semibold text-red-600">{{ __('Pasif — sitede görünmez') }}</span>@endunless</span>
                            </span>
                        </label>
                    @endforeach
                    <p class="hidden px-4 py-8 text-center text-sm text-slate-500" data-filter-empty>{{ __('Aramana uyan şehir kartı yok.') }}</p>
                </div>
                @include('admin.site-settings._filter-footer', ['unit' => __('şehir kartı')])
            </div>
            @error('hero_pass_id') <p class="mt-3 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </x-admin.card>

        {{-- ADIM 4: BLOG YAZISI --}}
        <x-admin.card class="scroll-mt-20 p-6 sm:p-8" id="adim-5" data-single="hero_guide_id">
            <div class="flex items-start gap-3">
                {!! $badge(5, 'bg-emerald-500') !!}
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ __('Sağ beyaz kart: hangi blog yazısı öne çıksın?') }}</p>
                    <p class="text-sm text-slate-600">{{ __('Listeden birini seç. Kartta başlığı, kısa özeti ve okuma süresi yazar.') }}</p>
                </div>
            </div>
            <p class="mt-5 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 ring-1 ring-emerald-200/70">
                {{ __('Şu an seçili:') }} <strong data-single-current></strong>
            </p>
            <div class="mt-4" data-filter>
                {!! $filterBar(__('Blog yazısı ara…'), $blogs->map($cityOf)->unique()->values()) !!}
                <div class="mt-3 max-h-[22rem] divide-y divide-slate-100 overflow-y-auto rounded-2xl ring-1 ring-slate-200" data-filter-list>
                    <label class="flex cursor-pointer items-center gap-3 px-3 py-2.5 transition hover:bg-slate-50 has-[:checked]:bg-emerald-50" data-filter-pinned>
                        <input type="radio" name="hero_guide_id" value="" class="size-4 border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(!$guideId) data-label="{{ __('Otomatik (her zaman en yeni yazı)') }}">
                        <span class="grid h-10 w-14 shrink-0 place-items-center rounded-lg bg-emerald-100">✨</span>
                        <span><span class="block text-sm font-semibold text-slate-900">{{ __('Otomatik') }}</span><span class="text-xs text-slate-500">{{ __('Her zaman en yeni yazı') }}</span></span>
                    </label>
                    @foreach ($blogs as $blog)
                        <label @class(['flex items-center gap-3 px-3 py-2.5 transition has-[:checked]:bg-emerald-50', 'cursor-pointer hover:bg-slate-50' => $blog->active, 'cursor-not-allowed opacity-50' => !$blog->active])
                            data-filter-item data-name="{{ $blog->title }}" data-keywords="{{ $keywords($blog, 'title') }}" data-city="{{ $cityOf($blog) }}" @unless ($blog->active) data-unavailable @endunless>
                            <input type="radio" name="hero_guide_id" value="{{ $blog->id }}" class="size-4 border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked($guideId === $blog->id) @disabled(!$blog->active) data-label="{{ $blog->title }}">
                            <span class="h-10 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                @if ($blog->cover_path) <img src="{{ $blog->coverUrl('sm') }}" alt="" loading="lazy" class="size-full object-cover" style="object-position: {{ $blog->cover_focus ?? 50 }}% center"> @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-slate-900" title="{{ $blog->title }}">{{ $blog->title }}</span>
                                <span class="text-xs text-slate-500">{{ $cityOf($blog) }} · {{ $blog->published_at?->translatedFormat('j F Y') }}@unless ($blog->active) · <span class="font-semibold text-red-600">{{ __('Yayında değil — sitede görünmez') }}</span>@endunless</span>
                            </span>
                        </label>
                    @endforeach
                    <p class="hidden px-4 py-8 text-center text-sm text-slate-500" data-filter-empty>{{ __('Aramana uyan yazı yok.') }}</p>
                </div>
                @include('admin.site-settings._filter-footer', ['unit' => __('yazı')])
            </div>
            @error('hero_guide_id') <p class="mt-3 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </x-admin.card>

    </form>

    {{-- Yazıları çevir: ana formun içinde form olamayacağı için ayrı; 2. adımdaki buton form="hero-translate" ile bağlı --}}
    <form id="hero-translate" action="{{ route('translations.translate', ['hero', $heroText->id]) }}" method="POST" class="hidden"
        data-confirm-title="{{ __('Yazılar diğer dillere çevrilsin mi?') }}"
        data-confirm="{{ __('Kaydedilmiş İngilizce yazı Google Çeviri ile çevrilir. Kaydetmediğin değişiklik varsa önce kaydet.') }}"
        data-confirm-button="{{ __('Evet, çevir') }}">
        @csrf
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('hero-settings');

            // AJAX kaydet: sayfa yenilenmeden kaydet
            const toast = document.createElement('div');
            toast.className = 'hidden fixed right-4 top-4 z-50 rounded-2xl px-5 py-3 text-sm font-semibold shadow-lg ring-1 max-w-[calc(100vw-2rem)]';
            document.body.appendChild(toast);
            const showToast = (msg, ok = true) => {
                toast.textContent = msg;
                toast.className = 'fixed right-4 top-4 z-50 rounded-2xl px-5 py-3 text-sm font-semibold shadow-lg ring-1 max-w-[calc(100vw-2rem)] ' + (ok ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-red-50 text-red-700 ring-red-200');
                clearTimeout(toast._t);
                toast._t = setTimeout(() => toast.classList.add('hidden'), 4000);
            };

            /* ---------- 1: resim önizleme, kadraj, karartma ---------- */
            const overlays = {
                soft: 'linear-gradient(90deg, rgba(2,6,23,.75), rgba(2,6,23,.35) 50%, rgba(2,6,23,0))',
                medium: 'linear-gradient(90deg, rgba(2,6,23,.9), rgba(2,6,23,.6) 50%, rgba(2,6,23,.1))',
                strong: 'linear-gradient(90deg, rgba(2,6,23,.95), rgba(2,6,23,.75) 50%, rgba(2,6,23,.3))',
            };
            const desktop = [...form.querySelectorAll('[data-preview=desktop], [data-preview=map]')];
            const mobile = form.querySelector('[data-preview=mobile]');
            const focus = form.querySelector('[data-hero-focus]');
            let hasMobile = {{ $hero['image_mobile_url'] ? 'true' : 'false' }};
            const maxBytes = {{ (int) min($maxMb, $serverMaxMb) * 1024 * 1024 }};
            const mb = (bytes) => (bytes / 1048576).toFixed(1) + ' MB';

            const applyOverlay = () => {
                const value = form.querySelector('[data-hero-overlay]:checked')?.value ?? 'medium';
                form.querySelectorAll('[data-preview-overlay]').forEach(el => el.style.background = overlays[value]);
            };
            const applyFocus = () => {
                desktop.forEach(img => img.style.objectPosition = focus.value + '% center');
                if (!hasMobile) mobile.style.objectPosition = focus.value + '% center';
            };

            // Büyük resim tarayıcıda küçültüldüyse (image-compress.js) eski/yeni boyutu sakla
            form.querySelectorAll('[data-hero-file]').forEach(input => input.addEventListener('image:compressed', (event) => {
                input.compressed = event.detail[0];
            }));

            form.querySelectorAll('[data-hero-file]').forEach(input => input.addEventListener('change', () => {
                const file = input.files[0];
                const kind = input.dataset.heroFile;
                const info = form.querySelector(`[data-file-info=${kind}]`);
                const compressed = input.compressed;
                input.compressed = null;
                if (!file) return;

                const url = URL.createObjectURL(file);
                const probe = new Image();
                probe.onload = () => {
                    const [w, h] = [probe.naturalWidth, probe.naturalHeight];
                    const small = kind === 'desktop' ? (w < 1600 || h < 800) : (w < 720 || h < 1100);
                    const wrongShape = kind === 'desktop' ? h > w : w > h;
                    const ideal = kind === 'desktop' ? w >= 2560 : w >= 1080;
                    const [tone, message] = file.size > maxBytes ? ['bad', @json(__('Bu resim çok büyük, daha küçük bir resim seç.'))]
                        : small ? ['bad', @json(__('Bu resim çok küçük, kabul edilmeyecek. Daha büyük bir resim seç.'))]
                        : wrongShape ? ['warn', kind === 'desktop' ? @json(__('Bu resim dik; yatay bir resim daha iyi görünür.')) : @json(__('Bu resim yatay; telefon için dik bir resim seç.'))]
                        : ideal ? ['good', @json(__('Harika, bu resim çok uygun. Kaydetmeyi unutma.'))]
                        : ['warn', @json(__('Olur. Daha büyük bir resim olsaydı büyük ekranlarda daha net görünürdü. Kaydetmeyi unutma.'))];

                    info.className = 'rounded-xl px-3 py-2 text-xs font-medium ring-1 ' + {
                        good: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                        warn: 'bg-amber-50 text-amber-800 ring-amber-200',
                        bad: 'bg-red-50 text-red-700 ring-red-200',
                    }[tone];
                    info.textContent = message + ` (${w} × ${h} px` + (compressed ? `, ${mb(compressed.before)} → ${mb(compressed.after)} ` + @json(__('otomatik küçültüldü')) : '') + ')';
                };
                probe.src = url;

                if (kind === 'desktop') {
                    desktop.forEach(img => { img.src = url; img.classList.remove('hidden'); });
                    if (!hasMobile) { mobile.src = url; mobile.classList.remove('hidden'); }
                    form.querySelector('[data-preview-placeholder="desktop"]')?.classList.add('hidden');
                    form.querySelector('[data-file-label=hero_image]').textContent = @json(__('Başka resim seç'));
                } else {
                    hasMobile = true;
                    mobile.src = url; mobile.classList.remove('hidden'); mobile.style.objectPosition = '50% center';
                }
            }));

            focus.addEventListener('input', applyFocus);
            form.querySelectorAll('[data-hero-overlay]').forEach(el => el.addEventListener('change', applyOverlay));
            applyOverlay();

            /* ---------- 2: yazı önizlemesi ---------- */
            // Başlık: dolu satırlar alt alta, kırmızı kelime en son dolu satırın sonuna (sitedeki gibi)
            const heroTitle = form.querySelector('[data-preview-title]');
            const heroValue = (field) => (form.querySelector(`[data-hero-text="${field}"]`)?.value || '').trim();
            const renderTitle = () => {
                const lines = ['title_1', 'title_2', 'title_3'].map(heroValue).filter(Boolean);
                const highlight = heroValue('title_highlight');
                if (!lines.length && highlight) lines.push('');
                heroTitle.replaceChildren(...lines.map((line, i) => {
                    const row = document.createElement('span');
                    row.className = 'block';
                    row.textContent = line;
                    if (highlight && i === lines.length - 1) {
                        const red = document.createElement('span');
                        red.className = 'text-red-500';
                        red.textContent = (line ? ' ' : '') + highlight;
                        row.append(red);
                    }
                    return row;
                }));
            };
            form.querySelectorAll('[data-hero-text]').forEach(input => {
                const target = form.querySelector(`[data-preview-text="${input.dataset.heroText}"]`);
                const update = () => target ? (target.textContent = input.value, target.classList.toggle('hidden', !input.value.trim())) : renderTitle();
                input.addEventListener('input', update);
                update();
            });

            /* ---------- Listeler: arama + şehir + görünmeyenleri göster ---------- */
            // Türkçe karakter ve büyük/küçük harf fark etmesin (ayasofya = Ayasofya, dolmabahce = Dolmabahçe)
            const normalize = (text) => String(text).toLocaleLowerCase('tr').replace(/ı/g, 'i').normalize('NFD').replace(/[̀-ͯ]/g, '');

            form.querySelectorAll('[data-filter]').forEach(list => {
                const search = list.querySelector('[data-filter-search]');
                const cityButtons = [...list.querySelectorAll('[data-filter-city]')];
                const items = [...list.querySelectorAll('[data-filter-item]')];
                const empty = list.querySelector('[data-filter-empty]');
                const count = list.querySelector('[data-filter-count]');
                const toggle = list.querySelector('[data-filter-hidden-toggle]');
                const hiddenTotal = items.filter(i => i.hasAttribute('data-unavailable')).length;
                let city = '';

                items.forEach(item => item.dataset.search = normalize(item.dataset.name + ' ' + (item.dataset.keywords ?? '')));
                if (hiddenTotal) {
                    toggle.classList.replace('hidden', 'flex');
                    toggle.querySelector('[data-filter-hidden-count]').textContent = hiddenTotal;
                }

                const apply = () => {
                    const words = normalize(search.value).split(/\s+/).filter(Boolean);
                    const showHidden = toggle.querySelector('input').checked;
                    let shown = 0;
                    items.forEach(item => {
                        const visible = words.every(w => item.dataset.search.includes(w))
                            && (!city || item.dataset.city === city)
                            && (showHidden || !item.hasAttribute('data-unavailable'));
                        item.classList.toggle('hidden', !visible);
                        if (visible) shown++;
                    });
                    empty.classList.toggle('hidden', shown > 0);
                    count.textContent = shown === items.length ? `${items.length} ${count.dataset.unit}` : `${shown} / ${items.length} ${count.dataset.unit}`;
                };

                search.addEventListener('input', apply);
                search.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
                cityButtons.forEach(button => button.addEventListener('click', () => {
                    city = button.dataset.filterCity;
                    cityButtons.forEach(b => b.toggleAttribute('data-active', b === button));
                    apply();
                }));
                toggle.querySelector('input').addEventListener('change', apply);
                apply();
            });

            /* ---------- 3 ve 4: tek seçim, "Şu an seçili" yazısı ---------- */
            form.querySelectorAll('[data-single]').forEach(card => {
                const current = card.querySelector('[data-single-current]');
                const update = () => current.textContent = card.querySelector(`input[name="${card.dataset.single}"]:checked`)?.dataset.label ?? '—';
                card.querySelectorAll('input[type=radio]').forEach(radio => radio.addEventListener('change', update));
                update();
            });

            /* ---------- 2: kayan kart — eklenme sırasıyla en fazla N aktivite ---------- */
            const box = form.querySelector('[data-featured]');
            const limit = Number(box.dataset.max);
            const inputs = box.querySelector('[data-featured-inputs]');
            const tray = box.querySelector('[data-featured-tray]');
            const rows = new Map([...box.querySelectorAll('[data-featured-row]')].map(row => [row.dataset.featuredRow, row]));
            const toggles = [...box.querySelectorAll('[data-featured-toggle]')];
            let picked = [...inputs.querySelectorAll('input')].map(i => i.value).filter(id => rows.has(id));
            const label = { add: @json(__('+ Ekle')), added: @json(__('✓ :n. sırada')), full: @json(__('Liste dolu')) };
            const escape = (text) => String(text).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

            const move = (id, step) => {
                const from = picked.indexOf(id), to = from + step;
                if (to < 0 || to >= picked.length) return;
                [picked[from], picked[to]] = [picked[to], picked[from]];
                render();
            };

            const render = () => {
                inputs.replaceChildren(...picked.map(id => Object.assign(document.createElement('input'), { type: 'hidden', name: 'hero_featured[]', value: id })));

                tray.innerHTML = picked.map((id, index) => {
                    const row = rows.get(id);
                    return `<li class="flex items-center gap-3 rounded-xl bg-white p-2 pr-2.5 ring-1 ring-amber-200">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-amber-500 text-xs font-bold text-white">${index + 1}</span>
                        <span class="h-9 w-12 shrink-0 overflow-hidden rounded-md bg-slate-100">${row.dataset.thumb ? `<img src="${escape(row.dataset.thumb)}" alt="" class="size-full object-cover">` : ''}</span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-900">${escape(row.dataset.name)}</span>
                        <span class="flex shrink-0 items-center gap-1">
                            <button type="button" data-move="${id}:-1" title="${@json(__('Yukarı al'))}" class="grid size-8 place-items-center rounded-lg text-slate-500 ring-1 ring-slate-200 hover:bg-slate-50 disabled:opacity-30" ${index === 0 ? 'disabled' : ''}>↑</button>
                            <button type="button" data-move="${id}:1" title="${@json(__('Aşağı al'))}" class="grid size-8 place-items-center rounded-lg text-slate-500 ring-1 ring-slate-200 hover:bg-slate-50 disabled:opacity-30" ${index === picked.length - 1 ? 'disabled' : ''}>↓</button>
                            <button type="button" data-remove="${id}" title="${@json(__('Çıkar'))}" class="grid size-8 place-items-center rounded-lg text-slate-500 ring-1 ring-slate-200 hover:bg-red-50 hover:text-red-600">✕</button>
                        </span>
                    </li>`;
                }).join('');

                toggles.forEach(button => {
                    const position = picked.indexOf(button.dataset.featuredToggle);
                    const full = position < 0 && picked.length >= limit;
                    button.disabled = full;
                    button.textContent = position >= 0 ? label.added.replace(':n', position + 1) : (full ? label.full : label.add);
                    button.className = 'shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 transition disabled:cursor-not-allowed disabled:opacity-40 ' +
                        (position >= 0 ? 'bg-amber-500 text-white ring-amber-500 hover:bg-amber-600' : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50');
                    rows.get(button.dataset.featuredToggle).classList.toggle('bg-amber-50/60', position >= 0);
                });

                box.querySelector('[data-featured-count]').textContent = @json(__(':count / :max seçili')).replace(':count', picked.length).replace(':max', limit);
                box.querySelector('[data-featured-empty]').classList.toggle('hidden', picked.length > 0);
                box.querySelector('[data-featured-clear]').classList.toggle('invisible', picked.length === 0);
            };

            toggles.forEach(button => button.addEventListener('click', () => {
                const id = button.dataset.featuredToggle;
                picked = picked.includes(id) ? picked.filter(p => p !== id) : [...picked, id].slice(0, limit);
                render();
            }));
            tray.addEventListener('click', (event) => {
                const moveButton = event.target.closest('[data-move]');
                const removeButton = event.target.closest('[data-remove]');
                if (moveButton) { const [id, step] = moveButton.dataset.move.split(':'); move(id, Number(step)); }
                if (removeButton) { picked = picked.filter(p => p !== removeButton.dataset.remove); render(); }
            });
            box.querySelector('[data-featured-clear]').addEventListener('click', () => { picked = []; render(); });
            render();
        })();
    </script>
@endpush

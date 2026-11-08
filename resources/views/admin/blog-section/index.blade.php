@extends('layouts.admin')

@section('title', 'Blog bölümü')

@php
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $picked = array_map('intval', (array) old('blogs', $picked));
    $translator = app(\App\Services\Translation\ModelTranslator::class);
    // Bir kaydın çeviri durumu: dil hapları + "çevir" butonu (butonlar formun dışındaki küçük formlara bağlı)
    $translations = function ($model, string $formId) use ($translator) {
        $status = $translator->status($model);
        $pending = count(array_filter($status, fn ($state) => $state !== 'ok'));
        return view('admin.blog-section._translations', compact('model', 'status', 'pending', 'formId'))->render();
    };
@endphp

@section('header')
    <x-admin.page-header :title="__('Blog bölümü')" :breadcrumb="['Yönetim' => route('management.index'), 'Bölümler' => route('site-settings.index')]"
        :description="__('Anasayfadaki blog yazıları alanı: başlığı, hangi yazıların öne çıkacağı ve yanındaki bülten kutusu.')">
        <x-slot:icon><path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2z"/><path d="M8 7h6M8 11h4"/></x-slot:icon>
        <x-admin.section-toggle section="blog" />
    </x-admin.page-header>
@endsection

@section('footer-action')
    <button type="submit" form="blog-section-form" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        {{ __('Değişiklikleri kaydet') }}
    </button>
@endsection

@section('content')
    <form id="blog-section-form" action="{{ route('blog-section.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- 1: BAŞLIK --}}
        <x-admin.card class="p-6 sm:p-8">
            <div class="flex items-start gap-3">
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-violet-500 text-sm font-bold text-white">1</span>
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ __('Bölümün başlığı') }}</p>
                    <p class="text-sm text-slate-600">{{ __('İngilizce yaz ve kaydet; sonra aşağıdan diğer dillere çevir.') }}</p>
                </div>
            </div>
            <div class="mt-5 grid gap-6 lg:grid-cols-5">
                <div class="space-y-4 lg:col-span-3">
                    @foreach ([
                        'eyebrow' => ['Üstteki küçük yazı', 'From the journal', 40],
                        'title' => ['Başlık (siyah kısım)', 'Stories worth', 60],
                        'title_highlight' => ['Başlık (kırmızı kısım)', 'your time.', 60],
                        'description' => ['Açıklama', 'Practical, honest guides…', 200],
                    ] as $name => [$label, $placeholder, $limit])
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">{{ __($label) }}</span>
                            <input type="text" name="section[{{ $name }}]" maxlength="{{ $limit }}" placeholder="{{ $placeholder }}" autocomplete="off" data-preview-input="s-{{ $name }}"
                                value="{{ old("section.$name", $section->getTranslation($name, 'en', false)) }}" class="{{ $field }}">
                            @error("section.$name") <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
                        </label>
                    @endforeach
                </div>
                <div class="lg:col-span-2">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Önizleme') }}</p>
                    <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-red-600" data-preview="s-eyebrow"></p>
                        <p class="mt-2 text-2xl font-extrabold leading-tight text-slate-900"><span data-preview="s-title"></span> <span class="text-red-600" data-preview="s-title_highlight"></span></p>
                        <p class="mt-2 text-sm text-slate-600" data-preview="s-description"></p>
                    </div>
                </div>
            </div>
            {!! $translations($section, 'translate-blog') !!}
        </x-admin.card>

        {{-- 2: YAZILAR --}}
        <x-admin.card class="p-6 sm:p-8" data-picker data-max="{{ $max }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-amber-500 text-sm font-bold text-white">2</span>
                    <div>
                        <p class="text-lg font-semibold text-slate-900">{{ __('Anasayfada gösterilecek yazılar') }}</p>
                        <p class="text-sm text-slate-600">{{ __('En fazla :max yazı ekle. 1. sıradaki büyük gösterilir, diğerleri yanında küçük.', ['max' => $max]) }}</p>
                    </div>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-800 ring-1 ring-amber-200" data-picker-count></span>
            </div>

            <div class="mt-5 rounded-2xl bg-amber-50/50 p-4 ring-1 ring-amber-200/70">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Seçtiklerin') }}</p>
                    <button type="button" class="text-xs font-semibold text-slate-500 hover:text-red-600" data-picker-clear>{{ __('Hepsini çıkar') }}</button>
                </div>
                <p class="mt-2 text-sm text-amber-900" data-picker-empty>{{ __('Hiçbir şey seçmezsen sorun değil: en yeni yayınlanan yazılar otomatik gösterilir. Şehir sayfalarında her zaman o şehrin yazıları çıkar.') }}</p>
                <ol class="mt-3 space-y-2" data-picker-tray></ol>
            </div>
            <div data-picker-inputs>
                @foreach ($picked as $id) <input type="hidden" name="blogs[]" value="{{ $id }}"> @endforeach
            </div>

            <div class="relative mt-4">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
                <input type="search" data-picker-search placeholder="{{ __('Yazı ara…') }}" autocomplete="off" class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm outline-none focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10">
            </div>
            <div class="mt-3 max-h-[24rem] divide-y divide-slate-100 overflow-y-auto rounded-2xl ring-1 ring-slate-200">
                @foreach ($blogs as $blog)
                    <div class="flex items-center gap-3 px-3 py-2.5" data-picker-row="{{ $blog->id }}" data-name="{{ $blog->title }}" data-thumb="{{ $blog->coverUrl('sm') }}"
                        data-search="{{ mb_strtolower(implode(' ', array_filter((array) $blog->getTranslations('title')))) }}">
                        <span class="h-10 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                            @if ($blog->cover_path) <img src="{{ $blog->coverUrl('sm') }}" alt="" loading="lazy" class="size-full object-cover" style="object-position: {{ $blog->cover_focus ?? 50 }}% center"> @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-slate-900">{{ $blog->title }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $blog->city?->name }} · {{ $blog->published_at?->translatedFormat('j F Y') }}@unless ($blog->active) · <span class="font-semibold text-red-600">{{ __('Yayında değil — sitede görünmez') }}</span>@endunless</span>
                        </span>
                        @if ($blog->active)
                            <button type="button" data-picker-toggle="{{ $blog->id }}" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1"></button>
                        @endif
                    </div>
                @endforeach
            </div>
            @error('blogs') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </x-admin.card>

        {{-- 3: BÜLTEN --}}
        <x-admin.card class="p-6 sm:p-8">
            <div class="flex items-start gap-3">
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-sky-500 text-sm font-bold text-white">3</span>
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ __('Bülten kutusu') }}</p>
                    <p class="text-sm text-slate-600">{{ __('Yazıların yanındaki “e-postanı bırak” kutusu. Abone olanlar Blog aboneleri sayfasına düşer.') }}</p>
                </div>
            </div>
            <div class="mt-5 grid gap-6 lg:grid-cols-5">
                <div class="space-y-4 lg:col-span-3">
                    @foreach ([
                        'title' => ['Başlık', 'New guides in your inbox', 60],
                        'description' => ['Açıklama', 'We only write when there is something worth reading.', 200],
                        'eyebrow' => ['Buton yazısı', 'Subscribe', 30],
                    ] as $name => [$label, $placeholder, $limit])
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">{{ __($label) }}</span>
                            <input type="text" name="newsletter[{{ $name }}]" maxlength="{{ $limit }}" placeholder="{{ $placeholder }}" autocomplete="off" data-preview-input="n-{{ $name }}"
                                value="{{ old("newsletter.$name", $newsletter->getTranslation($name, 'en', false)) }}" class="{{ $field }}">
                        </label>
                    @endforeach
                </div>
                <div class="lg:col-span-2">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Önizleme') }}</p>
                    <div class="rounded-2xl bg-slate-900 p-6 text-white">
                        <p class="text-lg font-bold" data-preview="n-title"></p>
                        <p class="mt-1.5 text-sm text-white/70" data-preview="n-description"></p>
                        <div class="mt-4 flex gap-2"><span class="flex-1 rounded-xl bg-white/10 px-3 py-2.5 text-xs text-white/50">email@…</span><span class="rounded-xl bg-red-600 px-4 py-2.5 text-xs font-semibold" data-preview="n-eyebrow"></span></div>
                    </div>
                </div>
            </div>
            {!! $translations($newsletter, 'translate-newsletter') !!}
        </x-admin.card>
    </form>

    {{-- Çeviri formları (ana formun içinde form olamaz; butonlar form="..." ile bağlı) --}}
    @foreach (['translate-blog' => $section, 'translate-newsletter' => $newsletter] as $formId => $model)
        <form id="{{ $formId }}" action="{{ route('translations.translate', ['section', $model->id]) }}" method="POST" class="hidden" data-busy="{{ __('Diğer dillere çevriliyor…') }}" data-busy-hint="{{ __('Google Çeviri her dili sırayla çeviriyor; 1–2 dakika sürebilir. Bitince sayfa kendiliğinden yenilenir, sayfayı kapatma.') }}"
            data-confirm-title="{{ __('Diğer dillere çevrilsin mi?') }}" data-confirm="{{ __('Kaydedilmiş İngilizce yazı Google Çeviri ile çevrilir. Kaydetmediğin değişiklik varsa önce kaydet.') }}" data-confirm-button="{{ __('Evet, çevir') }}">
            @csrf
        </form>
    @endforeach
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('blog-section-form');
            form.querySelectorAll('[data-preview-input]').forEach(input => {
                const target = form.querySelector(`[data-preview="${input.dataset.previewInput}"]`);
                const update = () => target.textContent = input.value || input.placeholder;
                input.addEventListener('input', update);
                update();
            });

            // Yazı seçici: eklenme sırasıyla en fazla N
            const box = form.querySelector('[data-picker]');
            const limit = Number(box.dataset.max);
            const rows = new Map([...box.querySelectorAll('[data-picker-row]')].map(row => [row.dataset.pickerRow, row]));
            const toggles = [...box.querySelectorAll('[data-picker-toggle]')];
            let picked = [...box.querySelectorAll('[data-picker-inputs] input')].map(i => i.value).filter(id => rows.has(id));
            const escape = (text) => String(text).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
            const render = () => {
                box.querySelector('[data-picker-inputs]').replaceChildren(...picked.map(id => Object.assign(document.createElement('input'), { type: 'hidden', name: 'blogs[]', value: id })));
                box.querySelector('[data-picker-tray]').innerHTML = picked.map((id, i) => {
                    const row = rows.get(id);
                    return `<li class="flex items-center gap-3 rounded-xl bg-white p-2 pr-2.5 ring-1 ring-amber-200">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-amber-500 text-xs font-bold text-white">${i + 1}</span>
                        <span class="h-9 w-12 shrink-0 overflow-hidden rounded-md bg-slate-100">${row.dataset.thumb ? `<img src="${escape(row.dataset.thumb)}" alt="" class="size-full object-cover">` : ''}</span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-900">${escape(row.dataset.name)}${i === 0 ? ` <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-800">${@json(__('büyük'))}</span>` : ''}</span>
                        <span class="flex shrink-0 gap-1">
                            <button type="button" data-move="${id}:-1" class="grid size-8 place-items-center rounded-lg text-slate-500 ring-1 ring-slate-200 hover:bg-slate-50 disabled:opacity-30" ${i === 0 ? 'disabled' : ''}>↑</button>
                            <button type="button" data-move="${id}:1" class="grid size-8 place-items-center rounded-lg text-slate-500 ring-1 ring-slate-200 hover:bg-slate-50 disabled:opacity-30" ${i === picked.length - 1 ? 'disabled' : ''}>↓</button>
                            <button type="button" data-remove="${id}" class="grid size-8 place-items-center rounded-lg text-slate-500 ring-1 ring-slate-200 hover:bg-red-50 hover:text-red-600">✕</button>
                        </span></li>`;
                }).join('');
                toggles.forEach(button => {
                    const position = picked.indexOf(button.dataset.pickerToggle);
                    const full = position < 0 && picked.length >= limit;
                    button.disabled = full;
                    button.textContent = position >= 0 ? @json(__('✓ :n. sırada')).replace(':n', position + 1) : (full ? @json(__('Liste dolu')) : @json(__('+ Ekle')));
                    button.className = 'shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 transition disabled:opacity-40 ' + (position >= 0 ? 'bg-amber-500 text-white ring-amber-500' : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50');
                });
                box.querySelector('[data-picker-count]').textContent = @json(__(':count / :max seçili')).replace(':count', picked.length).replace(':max', limit);
                box.querySelector('[data-picker-empty]').classList.toggle('hidden', picked.length > 0);
                box.querySelector('[data-picker-clear]').classList.toggle('invisible', picked.length === 0);
            };
            toggles.forEach(b => b.addEventListener('click', () => { const id = b.dataset.pickerToggle; picked = picked.includes(id) ? picked.filter(p => p !== id) : [...picked, id].slice(0, limit); render(); }));
            box.querySelector('[data-picker-tray]').addEventListener('click', (e) => {
                const move = e.target.closest('[data-move]'); const remove = e.target.closest('[data-remove]');
                if (move) { const [id, step] = move.dataset.move.split(':'); const from = picked.indexOf(id), to = from + Number(step); if (to >= 0 && to < picked.length) { [picked[from], picked[to]] = [picked[to], picked[from]]; render(); } }
                if (remove) { picked = picked.filter(p => p !== remove.dataset.remove); render(); }
            });
            box.querySelector('[data-picker-clear]').addEventListener('click', () => { picked = []; render(); });
            const normalize = (t) => String(t).toLocaleLowerCase('tr').replace(/ı/g, 'i').normalize('NFD').replace(/[̀-ͯ]/g, '');
            box.querySelector('[data-picker-search]').addEventListener('input', (e) => {
                const words = normalize(e.target.value).split(/\s+/).filter(Boolean);
                rows.forEach(row => row.classList.toggle('hidden', !words.every(w => normalize(row.dataset.search).includes(w))));
            });
            box.querySelector('[data-picker-search]').addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
            render();
        })();
    </script>
@endpush

@extends('layouts.admin')

@section('title', 'Nasıl çalışır')

@php
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $sectionStatus = app(\App\Services\Translation\ModelTranslator::class)->status($section);
    $sectionPending = count(array_filter($sectionStatus, fn ($state) => $state !== 'ok'));
@endphp

@section('header')
    <x-admin.page-header :title="__('Nasıl çalışır')" :breadcrumb="['Yönetim' => route('management.index'), 'Bölümler' => route('site-settings.index')]"
        :description="__('Anasayfadaki numaralı adımlar bölümü (01, 02, 03). Üstte bölümün başlığı, altta adımlar.')">
        <x-slot:icon><circle cx="5" cy="12" r="2" /><circle cx="12" cy="12" r="2" /><circle cx="19" cy="12" r="2" /><path d="M7 12h3M14 12h3" /></x-slot:icon>
        <x-admin.section-toggle section="steps" />
        <a href="{{ route('steps.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni adım') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="space-y-6">

        {{-- 1: BÖLÜM BAŞLIĞI --}}
        <x-admin.card class="p-6 sm:p-8">
            <x-admin.step-heading :title="__('Bölümün başlığı')" :description="__('İngilizce yaz ve kaydet. Sonra “Diğer dillere çevir” ile 12 dile çevrilir.')" />

            <form action="{{ route('steps.section') }}" method="POST" class="mt-5 grid gap-6 lg:grid-cols-5">
                @csrf
                @method('PUT')
                <div class="space-y-4 lg:col-span-3">
                    @foreach ([
                        'eyebrow' => ['Üstteki küçük yazı', 'How it works', 40],
                        'title' => ['Başlık (siyah kısım)', 'Plan your trip in', 60],
                        'title_highlight' => ['Başlık (kırmızı kısım)', 'three steps.', 60],
                        'description' => ['Açıklama', 'From first idea to ticket in hand…', 200],
                    ] as $name => [$label, $placeholder, $max])
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">{{ __($label) }}</span>
                            <input type="text" name="{{ $name }}" maxlength="{{ $max }}" placeholder="{{ $placeholder }}" autocomplete="off" data-section-input="{{ $name }}"
                                value="{{ old($name, $section->getTranslation($name, 'en', false)) }}" class="{{ $field }}">
                            @error($name) <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
                        </label>
                    @endforeach
                    <button type="submit" class="rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">{{ __('Başlığı kaydet') }}</button>
                </div>
                <div class="lg:col-span-2">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Önizleme') }}</p>
                    <div class="rounded-2xl bg-slate-50 p-6 text-center ring-1 ring-slate-200">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-red-600" data-section-preview="eyebrow"></p>
                        <p class="mt-2 text-2xl font-extrabold leading-tight text-slate-900"><span data-section-preview="title"></span> <span class="text-red-600" data-section-preview="title_highlight"></span></p>
                        <p class="mt-2 text-sm text-slate-600" data-section-preview="description"></p>
                    </div>
                </div>
            </form>

            {{-- çeviri durumu (yukarıdaki formun dışında: kendi formu var) --}}
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl px-5 py-4 ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold text-slate-900">{{ __('Diğer diller') }}</span>
                    @foreach (\App\Models\Language::targets() as $language)
                        @php $state = $sectionStatus[$language->code] ?? 'missing'; @endphp
                        <a href="{{ route('translations.edit', ['section', $section->id, $language->code]) }}" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-slate-200 hover:bg-slate-50">
                            <span class="size-2 rounded-full {{ ['ok' => 'bg-emerald-500', 'stale' => 'bg-amber-500'][$state] ?? 'bg-slate-300' }}"></span>{{ $language->native_name }}
                        </a>
                    @endforeach
                </div>
                @if ($sectionPending)
                    <form action="{{ route('translations.translate', ['section', $section->id]) }}" method="POST" data-translate="section:{{ $section->id }}"
                        data-confirm-title="{{ __('Başlık diğer dillere çevrilsin mi?') }}" data-confirm="{{ __('Kaydedilmiş İngilizce yazı Google Çeviri ile çevrilir.') }}" data-confirm-button="{{ __('Evet, çevir') }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700">{{ __('Diğer dillere çevir (:count)', ['count' => $sectionPending]) }}</button>
                    </form>
                @else
                    <span class="rounded-xl bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-700">✓ {{ __('Tüm diller güncel') }}</span>
                @endif
            </div>
        </x-admin.card>

        {{-- 2: ADIMLAR --}}
        <x-admin.card class="overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-6 sm:px-8">
                <x-admin.step-heading :title="__('Adımlar')" :description="__('Anasayfada bu sırayla 01, 02, 03… diye numaralanır. Telefonda yana kayar. En iyi 3 ya da 4 adım.')" />
            </div>
            @if ($steps->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Henüz adım yok') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Hiç adım yoksa bu bölüm anasayfada gizlenir.') }}</p>
                </div>
            @else
                <ul class="mt-4 divide-y divide-slate-100 border-t border-slate-100">
                    @foreach ($steps as $step)
                        <li @class(['flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50/60 sm:px-8', 'opacity-60' => !$step->active])>
                            <div class="flex shrink-0 flex-col gap-1">
                                @foreach ([-1 => 'M6 15l6-6 6 6', 1 => 'M6 9l6 6 6-6'] as $direction => $path)
                                    <form action="{{ route('steps.move', $step) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="step" value="{{ $direction }}">
                                        <button type="submit" @disabled(($direction < 0 && $loop->parent->first) || ($direction > 0 && $loop->parent->last))
                                            title="{{ $direction < 0 ? __('Yukarı al') : __('Aşağı al') }}"
                                            class="grid size-7 place-items-center rounded-lg text-slate-400 ring-1 ring-slate-200 transition hover:bg-white hover:text-brand-900 disabled:opacity-30">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}" /></svg>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-gradient-to-br from-rose-500 to-red-700 text-sm font-extrabold text-white">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-rose-50 text-rose-600">{!! \App\Support\FeatureIcons::svg($step->icon) !!}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900">{{ $step->title }}</p>
                                    @if ($step->otherLocales())
                                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600">+{{ count($step->otherLocales()) }} dil</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 line-clamp-1 text-sm text-slate-600">{{ $step->text }}</p>
                                @if ($step->cta)
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $step->cta }} → {{ \App\Http\Controllers\StepController::LINKS[$step->link] ?? $step->link }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <x-admin.toggle :action="route('steps.toggle', $step)" :active="$step->active" :label="__('Yayında')" />
                                <a href="{{ route('steps.edit', $step) }}" title="{{ __('Düzenle') }}" class="ml-2 grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-brand-900">
                                    <x-admin.icon name="pencil" class="size-[18px]" />
                                </a>
                                <form action="{{ route('steps.destroy', $step) }}" method="POST" data-confirm="{{ __('Bu adım silinsin mi?') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="{{ __('Sil') }}" class="grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                        <x-admin.icon name="trash" class="size-[18px]" />
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-section-input]').forEach(input => {
            const target = document.querySelector(`[data-section-preview="${input.dataset.sectionInput}"]`);
            const update = () => target.textContent = input.value || input.placeholder;
            input.addEventListener('input', update);
            update();
        });
    </script>
@endpush

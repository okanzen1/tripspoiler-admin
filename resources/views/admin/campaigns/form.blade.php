@extends('layouts.admin')

@php
    $editing = $campaign->exists;
    $steps = ['Kampanya', 'Kod ve bağlantı', 'Tarih ve yayın'];
    $stepFields = [['title', 'description', 'badge', 'note', 'partner_id'], ['code', 'url'], ['starts_at', 'ends_at', 'active']];
    if (!$editing) {
        $steps[] = 'Onay';
    }
    $start = collect($stepFields)->search(fn ($fields) => $errors->hasAny($fields));
    $field = 'mt-1.5 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
@endphp

@section('title', $editing ? 'Kampanyayı düzenle' : 'Yeni kampanya')

@section('header')
    <x-admin.page-header :title="$editing ? 'Kampanyayı düzenle' : 'Yeni kampanya'" :breadcrumb="['Kampanyalar' => route('campaigns.index')]"
        :description="$editing ? \Illuminate\Support\Str::limit($campaign->title, 90) : 'İndirim kodu ya da kampanya bağlantısı ekle; sitede Kampanyalar sayfasında görünür.'">
        <x-slot:icon><path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M2.5 7.5h19v4.5h-19zM12 21V7.5M12 7.5H8a2.5 2.5 0 1 1 0-5c2.5 0 4 5 4 5zM12 7.5h4a2.5 2.5 0 1 0 0-5c-2.5 0-4 5-4 5z" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    @if ($editing)
        <x-admin.translation-bar type="campaign" :model="$campaign" />
    @endif

    <x-admin.wizard :action="$editing ? route('campaigns.update', $campaign) : route('campaigns.store')" :method="$editing ? 'PUT' : null"
        :steps="$steps" :start="$start === false ? 0 : $start" :free="$editing"
        :submit-label="$editing ? 'Kaydet' : 'Kampanyayı ekle'" :cancel-url="route('campaigns.index')">

        <section data-step class="space-y-5">
            <x-admin.step-heading :title="__('Kampanya')" :description="__('İngilizce yaz; kaydettikten sonra yukarıdaki Çeviriler\'den diğer dillere çevir.')" />
            {{-- Partner: logolu kartlardan seç (logo kampanya satırında görünür) --}}
            <fieldset>
                <legend class="text-sm font-medium text-slate-700">{{ __('Partner (isteğe bağlı)') }}</legend>
                @php $selectedPartner = (string) old('partner_id', $campaign->partner_id); @endphp
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition has-[:checked]:border-brand-900 has-[:checked]:ring-4 has-[:checked]:ring-brand-900/10">
                        <input type="radio" name="partner_id" value="" class="sr-only" @checked($selectedPartner === '')>
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-400">—</span>
                        <span class="text-sm font-medium text-slate-600">{{ __('Partner yok') }}</span>
                    </label>
                    @foreach ($partners as $partner)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition has-[:checked]:border-brand-900 has-[:checked]:ring-4 has-[:checked]:ring-brand-900/10">
                            <input type="radio" name="partner_id" value="{{ $partner->id }}" class="sr-only" @checked($selectedPartner === (string) $partner->id)>
                            <x-admin.partner-logo :partner="$partner" class="size-10" />
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-900">{{ $partner->name }}</span>
                                @unless ($partner->logo_path)
                                    <span class="block text-[11px] text-amber-600">{{ __('Logo yok') }}</span>
                                @endunless
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    {{ __('Partnerin logosu kampanyada görünür. Logo yoksa baş harfleri çıkar.') }}
                    <a href="{{ route('partners.index') }}" target="_blank" class="font-semibold text-brand-900 hover:underline">{{ __('Partnerler › logo yükle') }} ↗</a>
                </p>
            </fieldset>
            <x-admin.input name="title" :label="__('Başlık')" :value="$campaign->getTranslation('title', 'en', false)" required maxlength="120"
                placeholder="{{ __('10% off the Istanbul Megapass') }}" autocomplete="off" />
            <x-admin.input name="badge" :label="__('Kısa etiket (isteğe bağlı)')" :value="$campaign->getTranslation('badge', 'en', false)" maxlength="30"
                placeholder="{{ __('10% OFF') }}" autocomplete="off" :hint="__('Kartın köşesindeki büyük yazı. Kısa tut: 10% OFF, Free entry…')" />
            <div>
                <label for="description" class="text-sm font-medium text-slate-700">{{ __('Açıklama (isteğe bağlı)') }}</label>
                <textarea id="description" name="description" rows="3" maxlength="500" placeholder="{{ __('Use the code at checkout on megapass.com. Valid for all pass durations.') }}"
                    @class([$field, 'resize-y leading-relaxed', 'border-slate-200' => !$errors->has('description'), 'border-red-300' => $errors->has('description')])>{{ old('description', $campaign->getTranslation('description', 'en', false)) }}</textarea>
                @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="note" class="text-sm font-medium text-slate-700">{{ __('Not (isteğe bağlı)') }}</label>
                <textarea id="note" name="note" rows="2" maxlength="300" placeholder="{{ __('The discount is applied automatically when you click the link.') }}"
                    @class([$field, 'resize-y leading-relaxed', 'border-slate-200' => !$errors->has('note'), 'border-red-300' => $errors->has('note')])>{{ old('note', $campaign->getTranslation('note', 'en', false)) }}</textarea>
                <p class="mt-1.5 text-xs text-slate-500">{{ __('Sitede kampanyanın içinde sarı bir bilgi satırı olarak görünür. Ör. “Bağlantıya tıkladığında indirim otomatik uygulanır.”') }}</p>
                @error('note') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Kod ve bağlantı')" :description="__('İkisinden en az biri. Kod verirsen sitede “Kopyala” düğmesi çıkar; bağlantı verirsen “Kampanyaya git” düğmesi.')" />
            <x-admin.input name="code" :label="__('İndirim kodu')" :value="$campaign->code" maxlength="60" placeholder="TRIPSPOILER10" autocomplete="off" class="font-mono uppercase tracking-wider" />
            <x-admin.input name="url" :label="__('Bağlantı')" :value="$campaign->url" maxlength="1000" placeholder="https://www.megapass.com/…" autocomplete="off"
                :hint="__('Partnerin affiliate bağlantısı ya da site içi adres (/activities/…). Tıklamalar Analitik\'te partnere sayılır.')" />
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Tarih ve yayın')" :description="__('Tarih boş kalırsa kampanya süresiz. Bitiş günü dahil görünür, ertesi gün kendiliğinden kalkar.')" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input type="date" name="starts_at" :label="__('Başlangıç (isteğe bağlı)')" :value="$campaign->starts_at?->toDateString()" />
                <x-admin.input type="date" name="ends_at" :label="__('Bitiş (isteğe bağlı)')" :value="$campaign->ends_at?->toDateString()" />
            </div>
            <x-admin.status-picker :active="$campaign->active" />
        </section>

        @unless ($editing)
            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Onay')" :description="__('Bilgileri kontrol et, doğruysa kampanyayı ekle.')" />
                <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
                    @foreach (['Başlık' => 'title', 'Etiket' => 'badge', 'Not' => 'note', 'Kod' => 'code', 'Bağlantı' => 'url', 'Başlangıç' => 'starts_at', 'Bitiş' => 'ends_at', 'Durum' => 'active'] as $label => $name)
                        <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                            <dt class="text-slate-500">{{ __($label) }}</dt>
                            <dd class="col-span-2 whitespace-pre-line break-all font-medium text-slate-900" data-summary-for="{{ $name }}">—</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endunless
    </x-admin.wizard>
@endsection

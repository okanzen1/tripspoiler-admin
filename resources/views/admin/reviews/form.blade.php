@extends('layouts.admin')

@php
    $editing = $review->exists;
    $steps = ['Yorum', 'Nerede görünecek', 'Onay durumu'];
    $stepFields = [['name', 'email', 'rating', 'comment'], ['activity_id'], ['approved']];
    if (!$editing) {
        $steps[] = 'Özet';
    }
    $start = collect($stepFields)->search(fn ($fields) => $errors->hasAny($fields));
    $field = 'mt-1.5 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10';
    $selectedActivity = (string) old('activity_id', $review->activity_id);
@endphp

@section('title', $editing ? 'Yorumu düzenle' : 'Yorum ekle')

@section('header')
    <x-admin.page-header :title="$editing ? __(':name adlı kişinin yorumu', ['name' => $review->name]) : 'Yorum ekle'" :breadcrumb="['Yorumlar' => route('reviews.index')]"
        :description="$editing ? $review->created_at->locale(app()->getLocale())->translatedFormat('j F Y H:i') : 'Örneğin e-postayla ya da partnerden gelen bir yorumu elle ekle.'">
        <x-slot:icon><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z" /><path d="M8.5 11h.01M12 11h.01M15.5 11h.01" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.wizard :action="$editing ? route('reviews.update', $review) : route('reviews.store')" :method="$editing ? 'PUT' : null"
        :steps="$steps" :start="$start === false ? 0 : $start" :free="$editing"
        :submit-label="$editing ? 'Kaydet' : 'Yorumu ekle'" :cancel-url="route('reviews.index')">

        <section data-step class="space-y-5">
            <x-admin.step-heading :title="__('Yorum')" :description="__('Yorumu yazan kişi, puanı ve yorum metni.')" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="name" :label="__('Ad soyad')" :value="$review->name" required maxlength="120" placeholder="{{ __('Emily Carter') }}" autocomplete="off" />
                <x-admin.input name="email" :label="__('E-posta (isteğe bağlı)')" type="email" :value="$review->email" maxlength="190" autocomplete="off"
                    :hint="__('Sitede gösterilmez; şifreli saklanır.')" />
            </div>
            <div>
                <span class="block text-sm font-medium text-slate-700">{{ __('Puan') }}</span>
                <div class="mt-1.5"><x-admin.rating-picker :value="$review->rating" /></div>
                @error('rating') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="comment" class="text-sm font-medium text-slate-700">{{ __('Yorum') }}</label>
                <textarea id="comment" name="comment" rows="5" required maxlength="2000" dir="auto" placeholder="{{ __('TripSpoiler made planning my trip so much easier…') }}"
                    @class([$field, 'resize-y leading-relaxed', 'border-slate-200' => !$errors->has('comment'), 'border-red-300' => $errors->has('comment')])>{{ old('comment', $review->comment) }}</textarea>
                @error('comment') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Nerede görünecek')" :description="__('Genel yorumlar anasayfada ve yorumlar sayfasında; aktivite yorumları o aktivitenin sayfasında görünür.')" />
            <div>
                <label for="activity_id" class="text-sm font-medium text-slate-700">{{ __('Yer') }}</label>
                <select id="activity_id" name="activity_id" class="{{ $field }} border-slate-200">
                    <option value="" @selected($selectedActivity === '')>{{ __('Genel (site hakkında)') }}</option>
                    @foreach ($activities as $city => $cityActivities)
                        <optgroup label="{{ __(':city aktiviteleri', ['city' => $city]) }}">
                            @foreach ($cityActivities as $activity)
                                <option value="{{ $activity->id }}" @selected($selectedActivity === (string) $activity->id)>{{ $activity->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        </section>

        <section data-step hidden class="space-y-5">
            <x-admin.step-heading :title="__('Onay durumu')" :description="__('Onaylı yorumlar sitede görünür; bekleyenler sadece panelde.')" />
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([1 => ['Onaylı', 'Sitede görünür'], 0 => ['Onay bekliyor', 'Sitede görünmez']] as $value => [$label, $hint])
                    <label class="flex cursor-pointer gap-3.5 rounded-2xl border-2 border-slate-200 p-5 transition hover:border-slate-300 has-checked:border-brand-900 has-checked:bg-brand-50/60">
                        <input type="radio" name="approved" value="{{ $value }}" data-label="{{ __($label) }}" required class="mt-0.5 size-4 accent-brand-900"
                            @checked((bool) old('approved', $review->approved) === (bool) $value) />
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">{{ __($label) }}</span>
                            <span class="mt-1 block text-xs text-slate-500">{{ __($hint) }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </section>

        @unless ($editing)
            <section data-step hidden class="space-y-5">
                <x-admin.step-heading :title="__('Özet')" :description="__('Bilgileri kontrol et, doğruysa yorumu ekle.')" />
                <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
                    @foreach (['Ad soyad' => 'name', 'Puan' => 'rating', 'Yorum' => 'comment', 'Yer' => 'activity_id', 'Onay durumu' => 'approved'] as $label => $name)
                        <div class="grid grid-cols-3 gap-4 px-5 py-3.5 text-sm">
                            <dt class="text-slate-500">{{ __($label) }}</dt>
                            <dd class="col-span-2 whitespace-pre-line font-medium text-slate-900" data-summary-for="{{ $name }}">—</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endunless
    </x-admin.wizard>
@endsection

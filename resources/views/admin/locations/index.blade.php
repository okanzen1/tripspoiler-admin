@extends('layouts.admin')

@section('title', 'Ülkeler ve şehirler')

@section('header')
    <x-admin.page-header :title="__('Ülkeler ve şehirler')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Bir şehrin sitede görünmesi için hem kendisi hem ülkesi aktif olmalı.')">
        <x-slot:icon><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" /></x-slot:icon>

        <div class="flex items-center divide-x divide-slate-200 rounded-2xl bg-slate-50 py-2 ring-1 ring-slate-200">
            <div class="px-5 text-center">
                <p class="text-xl font-semibold tabular-nums text-slate-900" data-active-count>{{ $activeCountryCount }}</p>
                <p class="text-xs text-slate-500">{{ __('aktif ülke') }}</p>
            </div>
            <div class="px-5 text-center">
                <p class="text-xl font-semibold tabular-nums text-slate-900">{{ $visibleCityCount }}</p>
                <p class="text-xs text-slate-500">{{ __('yayında şehir') }}</p>
            </div>
        </div>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="grid items-start gap-5 lg:grid-cols-[minmax(320px,400px)_1fr]">

        {{-- Sol: ülkeler --}}
        <x-admin.card class="flex flex-col overflow-hidden lg:sticky lg:top-6 lg:max-h-[calc(100vh-3rem)]">
            <div class="space-y-3 border-b border-slate-100 p-4">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input type="search" id="country-search" placeholder="{{ __('Ülke ara…') }}" autocomplete="off"
                        class="block w-full rounded-xl border-0 bg-slate-100/80 py-2.5 pl-10 pr-3 text-sm outline-none transition placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-brand-900/30" />
                </div>
                <div class="flex rounded-xl bg-slate-100 p-1 text-sm font-medium" role="tablist" aria-label="{{ __('Durum') }}">
                    @foreach (['all' => 'Tümü', 'active' => 'Aktif', 'passive' => 'Pasif'] as $value => $label)
                        <button type="button" role="tab" data-filter="{{ $value }}" aria-selected="{{ $value === 'all' ? 'true' : 'false' }}"
                            class="flex-1 rounded-lg px-3 py-1.5 text-slate-500 transition hover:text-slate-800 aria-selected:bg-white aria-selected:text-brand-900 aria-selected:shadow-sm">{{ __($label) }}</button>
                    @endforeach
                </div>
            </div>

            <ul id="country-list" class="relative flex-1 divide-y divide-slate-100 overflow-y-auto">
                @foreach ($countries as $country)
                    @php($isSelected = $country->is($selected))
                    <li data-search="{{ $searchKey($country) }}" data-active="{{ $country->active ? '1' : '0' }}"
                        @class(['flex items-center gap-3 pr-4 transition', 'bg-brand-50' => $isSelected, 'hover:bg-slate-50' => !$isSelected])
                        @if ($isSelected) data-selected @endif>
                        <a href="{{ route('locations.index', ['country' => $country->id]) }}"
                            @class(['flex min-w-0 flex-1 items-center gap-3 border-l-[3px] py-2.5 pl-4', 'border-brand-900' => $isSelected, 'border-transparent' => !$isSelected])
                            @if ($isSelected) aria-current="true" @endif>
                            <span class="text-xl leading-none" aria-hidden="true">{{ $country->flag }}</span>
                            <span class="min-w-0">
                                <span @class(['block truncate text-sm', 'font-semibold text-brand-900' => $isSelected, 'font-medium text-slate-800' => !$isSelected])>{{ $country->name_tr }}</span>
                                <span class="block truncate text-xs text-slate-400">
                                    {{ $country->cities_count ? __(':count şehir', ['count' => $country->cities_count]) : $country->name }}
                                </span>
                            </span>
                        </a>
                        <x-admin.toggle :action="route('countries.toggle', $country)" :active="$country->active" :label="__(':name aktif', ['name' => $country->name_tr])" />
                    </li>
                @endforeach
            </ul>
            <p id="country-empty" class="hidden px-6 py-10 text-center text-sm text-slate-500">{{ __('Ülke bulunamadı.') }}</p>
        </x-admin.card>

        {{-- Sağ: seçili ülke ve şehirleri --}}
        @if ($selected)
        <div class="space-y-5">
            <x-admin.card class="overflow-hidden">
                <div class="flex flex-wrap items-center gap-5 p-6">
                    <span class="text-5xl leading-none" aria-hidden="true">{{ $selected->flag }}</span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xl font-semibold text-slate-900">{{ $selected->name_tr }}</h2>
                        <p class="text-sm text-slate-500">{{ $selected->name }} · {{ $selected->code }}</p>
                    </div>
                    {{-- Para birimi: sitede döviz kuru şeridinde bu ülkenin şehirleri için kullanılır --}}
                    <form method="POST" action="{{ route('countries.currency', $selected) }}" class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 ring-1 ring-slate-200">
                        @csrf @method('PATCH')
                        <label for="country-currency" class="text-sm font-medium text-slate-700">{{ __('Para birimi') }}</label>
                        <select id="country-currency" name="currency" onchange="this.form.submit()"
                            class="max-w-[14rem] rounded-lg border-0 bg-transparent py-1 pl-1 pr-7 text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-brand-900/20">
                            @unless ($selected->currency)
                                <option value="" disabled selected>{{ __('Seç') }}</option>
                            @endunless
                            @foreach (\App\Models\Country::currencyOptions() as $code => $label)
                                <option value="{{ $code }}" @selected($selected->currency === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                    <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-2.5 ring-1 ring-slate-200">
                        <span class="text-sm font-medium text-slate-700" data-country-status>{{ __($selected->active ? 'Sitede görünüyor' : 'Pasif') }}</span>
                        <x-admin.toggle :action="route('countries.toggle', $selected)" :active="$selected->active" :label="__(':name aktif', ['name' => $selected->name_tr])" />
                    </div>
                </div>

                <div data-country-warning @class(['border-t border-amber-200 bg-amber-50 px-6 py-3 text-sm text-amber-900', 'hidden' => $selected->active || $cities->isEmpty()])>
                    {{ __('Ülke pasif olduğu için aşağıdaki şehirler aktif olsalar bile sitede görünmez.') }}
                </div>
            </x-admin.card>

            <x-admin.card class="overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ $selected->name_tr }} şehirleri</h3>
                        <p class="text-sm text-slate-500">
                            {{ number_format($cityTotals['all'], 0, ',', '.') }} şehir ·
                            <span data-city-active-count>{{ $cityTotals['active'] }}</span> {{ __('aktif') }}
                        </p>
                    </div>
                    <a href="{{ route('cities.create', ['country' => $selected->id]) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-brand-900">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                        {{ __('Listede yok mu? Elle ekle') }}
                    </a>
                </div>

                {{-- Şehir arama: seçili ülke içinde --}}
                <form method="GET" class="flex flex-wrap items-center gap-3 px-6 py-4">
                    <input type="hidden" name="country" value="{{ $selected->id }}" />
                    @if ($status)
                        <input type="hidden" name="status" value="{{ $status }}" />
                    @endif
                    <div class="relative min-w-56 flex-1">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                        <input type="search" name="q" value="{{ $search }}" placeholder="{{ $selected->name_tr }} içinde şehir ara… (ör. Bursa)" autocomplete="off"
                            class="block w-full rounded-xl border-0 bg-slate-100/80 py-2.5 pl-10 pr-3 text-sm outline-none transition placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-brand-900/30" />
                    </div>
                    <x-admin.status-tabs :current="$status" />
                </form>

                @if ($cities->isEmpty())
                    <div class="border-t border-slate-100 px-6 py-16 text-center">
                        <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-900">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21Z" /><circle cx="12" cy="9.5" r="2.5" /></svg>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-slate-900">
                            {{ $search ? "\"{$search}\" bulunamadı" : "{$selected->name_tr} için şehir yok" }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Listede olmayan küçük yerleri (ör. Göreme) "Elle ekle" ile ekleyebilirsin.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-y border-slate-100 bg-slate-50/60 text-xs text-slate-500">
                                <tr>
                                    <th class="px-6 py-3 font-medium">{{ __('Şehir') }}</th>
                                    <th class="px-6 py-3 font-medium">{{ __('URL adı') }}</th>
                                    <th class="px-6 py-3 font-medium">{{ __('Aktif') }}</th>
                                    <th class="px-6 py-3"><span class="sr-only">{{ __('İşlemler') }}</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($cities as $city)
                                    <tr class="transition hover:bg-slate-50/60">
                                        <td class="px-6 py-3">
                                            <p class="font-semibold text-slate-900">{{ $city->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $city->populationLabel() ? __(':count nüfus', ['count' => $city->populationLabel()]) : __('Elle eklendi') }}</p>
                                        </td>
                                        <td class="px-6 py-3">
                                            <form action="{{ route('cities.slug', $city) }}" method="POST" data-inline-slug
                                                class="group flex w-full max-w-xs items-center rounded-lg ring-1 ring-slate-200 transition focus-within:ring-2 focus-within:ring-brand-900/40 [&.saved]:ring-emerald-400">
                                                @csrf
                                                @method('PATCH')
                                                <span class="select-none pl-3 text-xs text-slate-400">{{ __('/cities/') }}</span>
                                                <input name="slug" value="{{ $city->slug }}" aria-label="{{ $city->name }} URL adı" spellcheck="false"
                                                    class="min-w-0 flex-1 rounded-r-lg bg-transparent py-1.5 pl-0.5 pr-3 text-sm text-slate-700 outline-none disabled:opacity-60" />
                                            </form>
                                        </td>
                                        <td class="px-6 py-3">
                                            <x-admin.toggle :action="route('cities.toggle', $city)" :active="$city->active" :label="__(':name aktif', ['name' => $city->name])" />
                                        </td>
                                        <td class="px-6 py-3">
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="{{ route('cities.edit', $city) }}" title="{{ __('Düzenle') }}" aria-label="{{ $city->name }} düzenle"
                                                    class="grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-brand-900">
                                                    <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-4-4L4 16v4ZM13.5 6.5l4 4" /></svg>
                                                </a>
                                                @if ($city->geoname_id)
                                                    <span class="size-9" aria-hidden="true"></span>
                                                @else
                                                    {{-- Sadece elle eklenen şehirler silinebilir; hazır listedekiler pasif yapılır --}}
                                                    <form action="{{ route('cities.destroy', $city) }}" method="POST" data-confirm="{{ __(':name silinsin mi?', ['name' => $city->name]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" title="{{ __('Sil') }}" aria-label="{{ $city->name }} sil"
                                                            class="grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                                            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" /></svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (!$search && $cityTotals['all'] > $cities->count())
                        <p class="border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                            Aktifler ve en kalabalık {{ \App\Http\Controllers\LocationController::CITY_LIMIT }} şehir gösteriliyor.
                            Diğer {{ number_format($cityTotals['all'] - $cities->count(), 0, ',', '.') }} şehir için yukarıdan ara.
                        </p>
                    @endif
                @endif
            </x-admin.card>
        </div>
        @else
        <div class="space-y-5">
            <x-admin.card class="overflow-hidden">
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-amber-50 text-amber-700">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" /></svg>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-900">{{ __('Henüz ülke yok') }}</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ __('Ülkeler yönetici panelinden eklenemez; veritabanı tohumlaması (seeder) ile yüklenir. Terminalde şu komutu çalıştırın:') }}
                    </p>
                    <pre class="mx-auto mt-4 max-w-md overflow-x-auto rounded-xl bg-slate-900 px-4 py-3 text-left text-xs text-slate-100">php artisan db:seed --class=CountrySeeder</pre>
                    <p class="mt-3 text-xs text-slate-400">{{ __('Ardından şehirleri yüklemek için: php artisan db:seed --class=CitySeeder') }}</p>
                </div>
            </x-admin.card>
        </div>
        @endif
    </div>
@endsection

@if ($selected)
@push('scripts')
    <script>
        (() => {
            const list = document.getElementById('country-list');
            const items = [...list.children];
            const search = document.getElementById('country-search');
            const empty = document.getElementById('country-empty');
            const tabs = [...document.querySelectorAll('[data-filter]')];
            const normalize = (s) => s.toLocaleLowerCase('tr').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ı/g, 'i');
            let filter = 'all';

            const apply = () => {
                const q = normalize(search.value.trim());
                let shown = 0;
                items.forEach((li) => {
                    const ok = (!q || li.dataset.search.includes(q))
                        && (filter === 'all' || (filter === 'active') === (li.dataset.active === '1'));
                    li.hidden = !ok;
                    shown += ok;
                });
                empty.classList.toggle('hidden', shown > 0);
            };

            search.addEventListener('input', apply);
            tabs.forEach((tab) => tab.addEventListener('click', () => {
                filter = tab.dataset.filter;
                tabs.forEach((t) => t.setAttribute('aria-selected', String(t === tab)));
                apply();
            }));

            // Seçili ülke listede görünür olsun (sadece liste kayar, sayfa değil)
            const selectedItem = list.querySelector('[data-selected]');
            if (selectedItem) list.scrollTop = selectedItem.offsetTop - list.clientHeight / 2 + selectedItem.offsetHeight / 2;

            // Ülke açılıp kapanınca: liste durumu + seçili ülke başlığı
            const selectedAction = @json(route('countries.toggle', $selected));
            const bump = (selector, active) => {
                const el = document.querySelector(selector);
                if (el) el.textContent = Number(el.textContent) + (active ? 1 : -1);
            };

            document.addEventListener('toggled', (e) => {
                const form = e.target.closest('form');
                if (form?.action.includes('/sehirler/')) return bump('[data-city-active-count]', e.detail.active);
                if (!form?.action.includes('/ulkeler/')) return;

                bump('[data-active-count]', e.detail.active);
                list.querySelectorAll(`form[action="${form.action}"]`).forEach((f) => (f.closest('li').dataset.active = e.detail.active ? '1' : '0'));
                if (form.action === selectedAction) {
                    document.querySelector('[data-country-status]').textContent = e.detail.active ? @json(__('Sitede görünüyor')) : @json(__('Pasif'));
                    document.querySelector('[data-country-warning]').classList.toggle('hidden', e.detail.active || {{ $cities->isEmpty() ? 'true' : 'false' }});
                }
            });
        })();
    </script>
@endpush
@endif

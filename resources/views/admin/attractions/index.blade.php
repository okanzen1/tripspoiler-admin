@extends('layouts.admin')

@section('title', 'Yerler')

@section('header')
    <x-admin.page-header :title="__('Yerler')" :description="__('Topkapı Sarayı, Akropolis gibi yer sayfaları: o yerin bütün biletleri, açılış saatleri, nasıl gidilir ve SSS. Google\'da “topkapi palace tickets” gibi aramalara cevap verir.')">
        <x-slot:icon><path d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6" /></x-slot:icon>
        <a href="{{ route('attractions.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni yer') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.todo-list :types="['attraction']" />
    <x-admin.content-todo :types="['attraction']" />
    <x-admin.badge-note :count="$missingImages" :text="__('Menüdeki kırmızı sayı: :n yerin resmi yok. Resimsiz yer sitede resimsiz görünür.', ['n' => $missingImages])"
        :href="route('attractions.index', ['image' => 'no'])" :link="__('Resmi eksikleri göster')" />
    <x-admin.card class="overflow-hidden">
        <x-admin.filter-bar :search="$search" placeholder="Yer adı ya da URL ara…"
            :tabs="['name' => 'status', 'value' => $status, 'options' => ['' => ['Tümü', $totalCount], 'active' => ['Yayında', $activeCount], 'passive' => ['Kapalı', $totalCount - $activeCount]]]"
            :filters="[
                ['name' => 'city', 'label' => 'Şehir', 'value' => $cityId, 'options' => $filterCities->mapWithKeys(fn ($c) => [$c->id => ($c->country?->flag ?? '') . ' ' . $c->name])->all(), 'icon' => 'pin'],
                ['name' => 'image', 'label' => 'Resim', 'value' => $image, 'options' => ['no' => __('Resmi eksik'), 'yes' => __('Resmi var')], 'icon' => 'image'],
            ]" />

        @if ($groups->isEmpty())
            <div class="border-t border-slate-100 px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ $filtered ? __('Yer bulunamadı') : __('Henüz yer yok') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $filtered ? __('Filtreyi değiştir.') : __('İlk yeri ekle (ör. Topkapı Sarayı) ve biletlerini bağla.') }}</p>
            </div>
        @else
            @foreach ($groups as $items)
                @php $city = $items->first()->city; @endphp
                {{-- şehir başlığı: o şehrin yerleri altında --}}
                <div class="flex items-center gap-2 border-t border-slate-100 bg-slate-50/80 px-6 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500">
                    <span>{{ $city?->country?->flag }} {{ $city?->name ?? __('Şehirsiz') }}</span>
                    <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold normal-case tracking-normal text-slate-500 ring-1 ring-slate-200">{{ __(':n yer', ['n' => $items->count()]) }}</span>
                    @if ($missing = $items->whereNull('image_path')->count())
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold normal-case tracking-normal text-rose-700 ring-1 ring-rose-200">{{ __(':n resim eksik', ['n' => $missing]) }}</span>
                    @endif
                </div>
                <ul class="divide-y divide-slate-100 border-t border-slate-100">
                    @foreach ($items as $attraction)
                        <li class="relative flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50/60">
                            <span class="size-14 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                                @if ($attraction->image_path)<img src="{{ $attraction->imageUrl('sm') }}" alt="" class="size-full object-cover">@else<span class="grid size-full place-items-center text-rose-300 ring-1 ring-inset ring-rose-200"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="9" cy="10" r="2" /><path d="m21 16-5-5L5 20" /></svg></span>@endif
                            </span>
                            <div class="min-w-0 flex-1">
                                {{-- satırın her yeri tıklanır (bağlantı satırı kaplar); sağdaki rozet ve "Sitede aç" üstte kalır --}}
                                <a href="{{ route('attractions.edit', $attraction) }}" class="block truncate font-semibold text-slate-900 after:absolute after:inset-0 hover:text-brand-900">{{ $attraction->getTranslation('name', 'en', false) }}</a>
                                <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                                    {{ __(':n aktivite bağlı', ['n' => $attraction->activities_count]) }}
                                    @unless ($attraction->image_path)<span class="rounded-md bg-rose-50 px-1.5 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-rose-200">{{ __('Resim eksik') }}</span>@endunless
                                </p>
                            </div>
                            <span class="relative z-10"><x-admin.todo-pill type="attraction" :id="$attraction->id" /></span>
                            {{-- Yayında/Kapalı: sayfa yenilenmeden açılıp kapanır (toggle.js); kapalıyken "Sitede aç" pasif --}}
                            <span data-attraction-state class="relative z-10 flex items-center gap-2">
                                <span data-state-label data-on="{{ __('Yayında') }}" data-off="{{ __('Kapalı') }}" @class(['w-14 text-right text-xs font-semibold', 'text-emerald-700' => $attraction->active, 'text-slate-500' => !$attraction->active])>{{ $attraction->active ? __('Yayında') : __('Kapalı') }}</span>
                                <x-admin.toggle :action="route('attractions.toggle', $attraction)" :active="$attraction->active" :label="__('Yayında')" />
                            </span>
                            <a data-site-link href="{{ config('site.url') }}/en/attractions/{{ $attraction->slug }}" target="_blank" rel="noopener" @class(['relative z-10 text-sm font-semibold text-slate-500 hover:text-brand-900', 'hidden' => !$attraction->active])>{{ __('Sitede aç') }} ↗</a>
                            <span data-site-off title="{{ __('Yayında değil, sitede görünmüyor') }}" @class(['relative z-10 cursor-not-allowed select-none text-sm font-semibold text-slate-300', 'hidden' => $attraction->active])>{{ __('Sitede aç') }} ↗</span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        @endif
    </x-admin.card>

    @push('scripts')
        <script>
            // Anahtar değişince yazıyı ve "Sitede aç"ı güncelle (sayfa yenilenmez)
            document.addEventListener('toggled', (e) => {
                const row = e.target.closest('li');
                const box = e.target.closest('[data-attraction-state]');
                if (!row || !box) return;
                const on = !!e.detail.active, label = box.querySelector('[data-state-label]');
                label.textContent = on ? label.dataset.on : label.dataset.off;
                label.classList.toggle('text-emerald-700', on);
                label.classList.toggle('text-slate-500', !on);
                row.querySelector('[data-site-link]')?.classList.toggle('hidden', !on);
                row.querySelector('[data-site-off]')?.classList.toggle('hidden', on);
            });
        </script>
    @endpush
@endsection

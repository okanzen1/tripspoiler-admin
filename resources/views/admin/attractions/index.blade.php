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
    <x-admin.card class="overflow-hidden">
        @if ($attractions->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ __('Henüz yer yok') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('İlk yeri ekle (ör. Topkapı Sarayı) ve biletlerini bağla.') }}</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($attractions as $attraction)
                    <li class="relative flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50/60">
                        <span class="size-14 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                            @if ($attraction->image_path)<img src="{{ $attraction->imageUrl('sm') }}" alt="" class="size-full object-cover">@endif
                        </span>
                        <div class="min-w-0 flex-1">
                            {{-- satırın her yeri tıklanır (bağlantı satırı kaplar); sağdaki rozet ve "Sitede aç" üstte kalır --}}
                            <a href="{{ route('attractions.edit', $attraction) }}" class="block truncate font-semibold text-slate-900 after:absolute after:inset-0 hover:text-brand-900">{{ $attraction->getTranslation('name', 'en', false) }}</a>
                            <p class="mt-0.5 text-sm text-slate-500">
                                {{ $attraction->city?->name }} · {{ __(':n aktivite bağlı', ['n' => $attraction->activities_count]) }}
                                @unless ($attraction->image_path)<span class="ms-1 font-semibold text-rose-600">· {{ __('Resim eksik') }}</span>@endunless
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

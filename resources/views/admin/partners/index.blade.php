@extends('layouts.admin')

@section('title', 'Partnerler')

@section('header')
    <x-admin.page-header :title="__('Partnerler')" :description="__('Affiliate partnerleri ve son 30 günde aldıkları tıklamalar.')" :breadcrumb="['Yönetim' => route('management.index')]">
        <x-slot:icon><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" /><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" /></x-slot:icon>
        <a href="{{ route('partners.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
            {{ __('Yeni partner') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.card class="overflow-hidden">
        @if ($partners->isEmpty())
            <div class="px-6 py-20 text-center">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-900">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" /><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" /></svg>
                </div>
                <h2 class="mt-5 text-lg font-semibold text-slate-900">{{ __('Henüz partner yok') }}</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">{{ __('GetYourGuide, Viator gibi affiliate partnerlerini ekle; aktivitelerin linkleri bunlara bağlanacak.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/60 text-xs text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Partner') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Kod') }}</th>
                            <th class="px-6 py-3 text-right font-medium">{{ __('Tıklama (30 gün)') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Aktif') }}</th>
                            <th class="px-6 py-3 font-medium" title="{{ __('Footer’ın üstündeki “Destekçilerimiz ve partnerlerimiz” şeridinde görünsün mü') }}">{{ __('Partnerler şeridinde') }}</th>
                            <th class="px-6 py-3"><span class="sr-only">{{ __('İşlemler') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($partners as $partner)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3.5">
                                        <x-admin.partner-logo :partner="$partner" class="size-11" />
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-900">{{ $partner->name }}</p>
                                            @if ($partner->website)
                                                <a href="{{ $partner->website }}" target="_blank" rel="noopener" class="truncate text-slate-500 hover:text-brand-900 hover:underline">{{ $partner->websiteHost() }}</a>
                                            @else
                                                <p class="text-slate-400">{{ __('Web sitesi yok') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5"><code class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">{{ $partner->slug }}</code></td>
                                <td class="px-6 py-3.5 text-right font-semibold tabular-nums text-slate-900">{{ number_format($clicks[$partner->slug] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-3.5">
                                    <x-admin.toggle :action="route('partners.toggle', $partner)" :active="$partner->active" :label="__(':name aktif', ['name' => $partner->name])" />
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <x-admin.toggle :action="route('partners.showcase', $partner)" :active="$partner->show_on_site" :label="__(':name partnerler şeridinde', ['name' => $partner->name])" />
                                        @unless ($partner->logo_path)
                                            <span class="text-xs text-amber-700" title="{{ __('Logo yüklenince görünür') }}">{{ __('logo yok') }}</span>
                                        @endunless
                                    </div>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('partners.edit', $partner) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-100 hover:ring-blue-200">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-4-4L4 16v4ZM13.5 6.5l4 4" /></svg>
                                            <span class="hidden sm:inline">{{ __('Düzenle') }}</span>
                                        </a>
                                        <form action="{{ route('partners.destroy', $partner) }}" method="POST" data-confirm="{{ __(':name silinsin mi?', ['name' => $partner->name]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="{{ __('Sil') }}" aria-label="{{ $partner->name }} sil"
                                                class="grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                {{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $partners->count() }}</strong> {{ __('partner') }}
            </p>
        @endif
    </x-admin.card>
@endsection

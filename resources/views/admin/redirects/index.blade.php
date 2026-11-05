@extends('layouts.admin')

@section('title', 'Yönlendirmeler')

@php($input = 'block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-sm outline-none transition placeholder:font-sans placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10')

@section('header')
    <x-admin.page-header :title="__('Yönlendirmeler')" :breadcrumb="['Yönetim' => route('management.index')]"
        :description="__('Eski adres → yeni adres (301). Bir slug değişince otomatik eklenir; Google eski linkte 404 görmez.')">
        <x-slot:icon><path d="M4 7h11l-3-3M20 17H9l3 3" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.card class="overflow-hidden">
        <form action="{{ route('redirects.store') }}" method="POST" class="grid gap-3 border-b border-slate-100 p-5 lg:grid-cols-[1fr_auto_1fr_auto] lg:items-start">
            @csrf
            <div>
                <input name="from_path" value="{{ old('from_path') }}" required maxlength="700" aria-label="{{ __('Eski adres') }}" placeholder="{{ __('Eski adres, ör. /de/activities/eski-adres') }}" class="{{ $input }}" />
                @error('from_path') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <span class="hidden pt-2.5 text-slate-400 lg:block">→</span>
            <div>
                <input name="to_path" value="{{ old('to_path') }}" required maxlength="700" aria-label="{{ __('Yeni adres') }}" placeholder="{{ __('Yeni adres, ör. /de/activities/yeni-adres') }}" class="{{ $input }}" />
                @error('to_path') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                <x-admin.icon name="plus" /> {{ __('Ekle') }}
            </button>
        </form>

        <x-admin.filter-bar :search="$search" placeholder="Adreste ara…"
            :filters="[['name' => 'locale', 'label' => 'Dil', 'value' => $locale, 'options' => $languages, 'icon' => 'globe']]" />

        @if ($redirects->isEmpty())
            <div class="border-t border-slate-100 px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ __('Yönlendirme yok') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('Bir aktivitenin, yazının ya da şehrin adresini değiştirdiğinde burada görünür.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-y border-slate-100 bg-slate-50/60 text-left text-xs text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Eski adres') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Yeni adres') }}</th>
                            <th class="px-6 py-3 text-right font-medium">{{ __('Kullanım') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($redirects as $redirect)
                            <tr class="hover:bg-slate-50/60">
                                <td class="max-w-md truncate px-6 py-3 font-mono text-xs text-slate-500" title="{{ $redirect->from_path }}" dir="auto">{{ $redirect->from_path }}</td>
                                <td class="max-w-md truncate px-6 py-3 font-mono text-xs text-slate-900" title="{{ $redirect->to_path }}">{{ $redirect->to_path }}</td>
                                <td class="px-6 py-3 text-right tabular-nums text-slate-600" title="{{ $redirect->last_hit_at?->locale(app()->getLocale())->translatedFormat('j M Y H:i') }}">{{ number_format($redirect->hits, 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right">
                                    <form action="{{ route('redirects.destroy', $redirect) }}" method="POST" data-confirm="{{ __('Bu yönlendirme silinsin mi? Eski adres 404 verir.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="{{ __('Sil') }}" aria-label="{{ __('Sil') }}" class="grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                            <x-admin.icon name="trash" />
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                <span>{{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ number_format($redirects->total(), 0, ',', '.') }}</strong></span>
                @if ($redirects->hasPages()) {{ $redirects->links() }} @endif
            </div>
        @endif
    </x-admin.card>
@endsection

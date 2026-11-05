@extends('layouts.admin')

@section('title', 'Blog aboneleri')

@section('header')
    <x-admin.page-header :title="__('Blog aboneleri')" :description="__('Sitedeki blog abonelik formundan gelen e-postalar.')">
        <x-slot:icon><path d="M4 4h12a4 4 0 0 1 4 4v12H8a4 4 0 0 1-4-4Z" /><path d="M8 9h8M8 13h5" /></x-slot:icon>
        <a href="{{ route('subscribers.export') }}"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
            <x-admin.icon name="download" /> {{ __('CSV indir') }}
        </a>
        <a href="{{ route('mailbox.bulk.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="mail" /> {{ __('Tümüne e-posta gönder') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        @foreach ([['Aktif abone', $activeCount], ['Bu ay katılan', $newThisMonth], ['Toplam kayıt', $totalCount]] as [$label, $value])
            <x-admin.card class="p-5">
                <p class="text-sm text-slate-500">{{ __($label) }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ number_format($value, 0, ',', '.') }}</p>
            </x-admin.card>
        @endforeach
    </div>

    <x-admin.card class="overflow-hidden">
        <x-admin.filter-bar :search="$search" placeholder="Tam e-posta adresiyle ara…"
            :tabs="['name' => 'status', 'value' => $status, 'options' => ['' => ['Tümü', $totalCount], 'active' => ['Aktif', $activeCount], 'passive' => ['Ayrıldı', $totalCount - $activeCount]]]" />

        @if ($subscribers->isEmpty())
            <div class="border-t border-slate-100 px-6 py-16 text-center">
                <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-900"><x-admin.icon name="mail" class="size-6" /></div>
                <p class="mt-4 text-sm font-semibold text-slate-900">{{ __($search ? 'Bu adreste abone yok' : 'Henüz abone yok') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('E-postalar şifreli saklandığı için arama tam adresle yapılır.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-slate-100 bg-slate-50/60 text-xs text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('E-posta') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Abone olma') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Durum') }}</th>
                            <th class="px-6 py-3"><span class="sr-only">{{ __('İşlemler') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($subscribers as $subscriber)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand-50 text-sm font-semibold uppercase text-brand-900">{{ mb_substr($subscriber->email, 0, 1) }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-900">{{ $subscriber->email }}</p>
                                            @if ($subscriber->source_path)
                                                <p class="truncate text-xs text-slate-500">{{ $subscriber->source_path }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 text-slate-600">{{ $subscriber->created_at->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                <td class="px-6 py-3.5">
                                    @if ($subscriber->isActive())
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"><span class="size-1.5 rounded-full bg-emerald-500"></span>{{ __('Aktif') }}</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600" title="{{ $subscriber->unsubscribed_at->format('d.m.Y H:i') }}"><span class="size-1.5 rounded-full bg-slate-400"></span>{{ __('Ayrıldı') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('mailbox.compose', ['to' => $subscriber->email]) }}" title="{{ __('E-posta gönder') }}" aria-label="{{ $subscriber->email }} adresine e-posta gönder"
                                            class="grid size-9 place-items-center rounded-lg text-brand-900 transition hover:bg-brand-50">
                                            <x-admin.icon name="mail" class="size-[18px]" />
                                        </a>
                                        <form action="{{ route('subscribers.destroy', $subscriber) }}" method="POST" data-confirm="{{ __(':email listeden silinsin mi?', ['email' => $subscriber->email]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="{{ __('Sil') }}" aria-label="{{ $subscriber->email }} sil"
                                                class="grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                                <x-admin.icon name="trash" class="size-[18px]" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                <span>{{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $subscribers->total() }}</strong> {{ __('kayıt') }}</span>
                @if ($subscribers->hasPages()) {{ $subscribers->links() }} @endif
            </div>
        @endif
    </x-admin.card>
@endsection

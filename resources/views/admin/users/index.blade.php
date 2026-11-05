@extends('layouts.admin')

@section('title', 'Kullanıcılar')

@section('header')
    <x-admin.page-header :title="__('Kullanıcılar')" :description="__('Panele erişimi olan admin ve editörler.')" :breadcrumb="['Yönetim' => route('management.index')]">
        <x-slot:icon><circle cx="9" cy="8" r="3.5" /><path d="M2.5 20c.8-3.4 3.4-5.5 6.5-5.5s5.7 2.1 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c1.9.8 3.1 2.6 3.5 5.2" /></x-slot:icon>
        <a href="{{ route('users.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>
            {{ __('Yeni kullanıcı') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')

    <x-admin.card class="overflow-hidden">

        {{-- Filtreler --}}
        <x-admin.filter-bar :search="$search" placeholder="Ad, soyad veya e-posta ara…"
            :tabs="['name' => 'role', 'value' => $role?->value, 'options' => ['' => ['Tümü', null]] + collect(\App\Enums\Role::cases())->mapWithKeys(fn ($c) => [$c->value => [$c->label(), null]])->all()]" />

        @if ($users->isEmpty())
            <div class="border-t border-slate-100 px-6 py-16 text-center">
                <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-900">{{ __('Kullanıcı bulunamadı') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('Arama ya da filtreyi değiştirmeyi dene.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-slate-100 bg-slate-50/60 text-xs font-medium text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Kullanıcı') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Rol') }}</th>
                            <th class="hidden px-6 py-3 font-medium md:table-cell">{{ __('Eklenme tarihi') }}</th>
                            <th class="px-6 py-3"><span class="sr-only">{{ __('İşlemler') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $item)
                            <tr class="group transition hover:bg-brand-50/40">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3.5">
                                        <x-admin.avatar :user="$item" class="size-10" />
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-900">
                                                {{ $item->full_name }}
                                                @if ($item->is(auth()->user()))
                                                    <span class="ml-1.5 rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-500">{{ __('Sen') }}</span>
                                                @endif
                                            </p>
                                            <p class="truncate text-slate-500">{{ $item->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><x-admin.role-badge :role="$item->role" /></td>
                                <td class="hidden px-6 py-4 text-slate-500 md:table-cell">{{ $item->created_at?->locale(app()->getLocale())->translatedFormat('d M Y') }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('users.edit', $item) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-white hover:text-brand-700 hover:ring-brand-200">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-4-4L4 16v4ZM13.5 6.5l4 4" />
                                            </svg>
                                            <span class="hidden sm:inline">{{ __('Düzenle') }}</span>
                                        </a>
                                        @unless ($item->is(auth()->user()))
                                            <form action="{{ route('users.destroy', $item) }}" method="POST" data-confirm="{{ __(':name silinsin mi?', ['name' => $item->full_name]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="{{ __('Sil') }}" aria-label="{{ $item->full_name }} sil"
                                                    class="grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                                    <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <span class="size-8" aria-hidden="true"></span>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                <span>{{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $users->total() }}</strong> {{ __('kullanıcı') }}</span>
                @if ($users->hasPages())
                    {{ $users->links() }}
                @endif
            </div>
        @endif
    </x-admin.card>
@endsection

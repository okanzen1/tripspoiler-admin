@extends('layouts.admin')

@section('title', 'Duyurular')

@section('header')
    <x-admin.page-header :title="__('Duyurular')" :description="__('Sitenin en üstündeki kayan şerit. Yayındaki duyurular sırayla döner; hiçbiri yayında değilse şerit gizlenir.')">
        <x-slot:icon><path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z" /><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13" /></x-slot:icon>
        <x-admin.section-toggle section="announcements" />
        <a href="{{ route('announcements.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni duyuru') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.todo-list :types="['announcement']" />
    <x-admin.card class="overflow-hidden">
        @if ($announcements->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ __('Henüz duyuru yok') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('İlk duyuruyu ekle; sitenin en üstünde kayan yazı olarak görünür.') }}</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($announcements as $announcement)
                    <li class="flex items-start gap-4 px-6 py-4 transition hover:bg-slate-50/60">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($announcement->link)
                                    <span class="max-w-80 truncate rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-900" title="{{ $announcement->link }}">{{ $announcement->link }}</span>
                                @endif
                                @if ($announcement->otherLocales())
                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600" title="{{ strtoupper(implode(', ', $announcement->otherLocales())) }}">+{{ count($announcement->otherLocales()) }} dil</span>
                                @endif
                            </div>
                            <p class="mt-1.5 font-semibold text-slate-900">{{ $announcement->text }} <x-admin.todo-pill type="announcement" :id="$announcement->id" class="ml-1 align-middle" /></p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5 pt-1">
                            <x-admin.toggle :action="route('announcements.toggle', $announcement)" :active="$announcement->active" :label="__('Yayında')" />
                            <a href="{{ route('announcements.edit', $announcement) }}" title="{{ __('Düzenle') }}" aria-label="{{ __('Düzenle') }}"
                                class="ml-2 grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-brand-900">
                                <x-admin.icon name="pencil" class="size-[18px]" />
                            </a>
                            <form action="{{ route('announcements.destroy', $announcement) }}" method="POST" data-confirm="{{ __('Bu duyuru silinsin mi?') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="{{ __('Sil') }}" aria-label="{{ __('Sil') }}" class="grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                    <x-admin.icon name="trash" class="size-[18px]" />
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                {{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $announcements->count() }}</strong> {{ __('duyuru') }},
                <strong class="font-semibold text-slate-700">{{ $announcements->where('active', true)->count() }}</strong> {{ __('yayında') }}
            </div>
        @endif
    </x-admin.card>
@endsection

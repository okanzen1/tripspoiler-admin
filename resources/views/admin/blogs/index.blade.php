@extends('layouts.admin')

@section('title', 'Blog')

@section('header')
    <x-admin.page-header :title="__('Blog')" description="{{ $activeCount }} yayında · toplam {{ $totalCount }} yazı">
        <x-slot:icon><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></x-slot:icon>
        <a href="{{ route('blogs.order') }}"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">
            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
            {{ __('Sıralama') }}
        </a>
        <a href="{{ route('blogs.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni yazı') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.card class="overflow-hidden">
        <x-admin.filter-bar :search="$search" placeholder="Başlık ya da URL ara…"
            :tabs="['name' => 'status', 'value' => $status, 'options' => ['' => ['Tümü', $totalCount], 'active' => ['Yayında', $activeCount], 'passive' => ['Taslak', $totalCount - $activeCount]]]"
            :filters="[
                ['name' => 'city', 'label' => 'Şehir', 'value' => $cityId, 'options' => ['none' => __('Şehre bağlı değil')] + $filterCities->mapWithKeys(fn ($c) => [$c->id => $c->country->flag . ' ' . $c->name])->all(), 'icon' => 'pin'],
                ['name' => 'cover', 'label' => 'Kapak', 'value' => $cover, 'options' => ['yes' => __('Kapağı olanlar'), 'no' => __('Kapağı olmayanlar')], 'icon' => 'image'],
            ]" />

        @if ($blogs->isEmpty())
            <div class="border-t border-slate-100 px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ __('Yazı bulunamadı') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('Filtreyi değiştir ya da yeni bir yazı ekle.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-slate-100 bg-slate-50/60 text-xs text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Yazı') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Şehir') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Yayın tarihi') }}</th>
                            <th class="px-6 py-3 text-right font-medium">{{ __('Okunma (30 gün)') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Yayında') }}</th>
                            <th class="px-6 py-3"><span class="sr-only">{{ __('İşlemler') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($blogs as $blog)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3.5">
                                        @if ($url = $blog->coverUrl('sm'))
                                            <img src="{{ $url }}" alt="" loading="lazy" class="h-12 w-16 shrink-0 rounded-lg object-cover ring-1 ring-slate-200" style="object-position: {{ $blog->cover_focus ?? 50 }}% center" />
                                        @else
                                            <span class="grid h-12 w-16 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200" title="{{ __('Kapak görseli yok') }}">
                                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="9" cy="10" r="2" /><path d="m21 16-5-5L5 20" /></svg>
                                            </span>
                                        @endif
                                        <div class="min-w-0 max-w-lg">
                                            <a href="{{ route('blogs.edit', $blog) }}" class="line-clamp-1 font-semibold text-slate-900 hover:text-brand-900 hover:underline">{{ $blog->title }}</a>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                                <x-admin.todo-pill type="blog" :id="$blog->id" />
                                                <span>{{ $blog->readingMinutes() }} dk okuma</span>
                                                @if ($blog->activities_count)
                                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 font-medium text-slate-600">{{ $blog->activities_count }} aktivite</span>
                                                @endif
                                                @if ($blog->otherLocales())
                                                    <span class="rounded-md bg-brand-50 px-1.5 py-0.5 font-medium text-brand-900" title="{{ strtoupper(implode(', ', $blog->otherLocales())) }}">+{{ count($blog->otherLocales()) }} dil</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-slate-700">{{ $blog->city ? $blog->city->country->flag . ' ' . $blog->city->name : '—' }}</td>
                                <td class="whitespace-nowrap px-6 py-3 text-slate-600">{{ $blog->published_at?->locale(app()->getLocale())->translatedFormat('j M Y') ?? '—' }}</td>
                                <td class="px-6 py-3 text-right font-semibold tabular-nums text-slate-900">{{ number_format($views[$blog->id] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-3">
                                    <x-admin.toggle :action="route('blogs.toggle', $blog)" :active="$blog->active" :label="__(':name yayında', ['name' => $blog->title])" />
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('blogs.edit', $blog) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-100 hover:ring-blue-200">
                                            <x-admin.icon name="pencil" /> <span class="hidden xl:inline">{{ __('Düzenle') }}</span>
                                        </a>
                                        <form action="{{ route('blogs.destroy', $blog) }}" method="POST" data-confirm="{{ __('":name" silinsin mi?', ['name' => $blog->title]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="{{ __('Sil') }}" aria-label="{{ $blog->title }} sil"
                                                class="grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
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
                <span>{{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $blogs->total() }}</strong> {{ __('yazı') }}</span>
                @if ($blogs->hasPages()) {{ $blogs->links() }} @endif
            </div>
        @endif
    </x-admin.card>
@endsection

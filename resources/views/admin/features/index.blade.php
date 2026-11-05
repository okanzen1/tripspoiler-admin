@extends('layouts.admin')

@section('title', 'Özellik kartları')

@section('header')
    <x-admin.page-header :title="__('Özellik kartları')" :breadcrumb="['Yönetim' => route('management.index'), 'Bölümler' => route('site-settings.index')]"
        :description="__('Anasayfada telefonlu bölümün altında yan yana kayan küçük kartlar. Her kartta bir ikon, kısa bir başlık ve bir cümle olur.')">
        <x-slot:icon><rect x="3" y="5" width="7" height="14" rx="2" /><rect x="14" y="5" width="7" height="14" rx="2" /></x-slot:icon>
        <x-admin.section-toggle section="features" />
        <a href="{{ route('features.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni kart') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.card class="overflow-hidden">
        @if ($features->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ __('Henüz kart yok') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('İlk kartı ekle; anasayfada hemen görünür. Hiç kart yoksa bu bölüm gizlenir.') }}</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($features as $feature)
                    <li @class(['flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50/60', 'opacity-60' => !$feature->active])>
                        {{-- sıra --}}
                        <div class="flex shrink-0 flex-col gap-1">
                            @foreach ([-1 => 'M6 15l6-6 6 6', 1 => 'M6 9l6 6 6-6'] as $step => $path)
                                <form action="{{ route('features.move', $feature) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="step" value="{{ $step }}">
                                    <button type="submit" @disabled(($step < 0 && $loop->parent->first) || ($step > 0 && $loop->parent->last))
                                        title="{{ $step < 0 ? __('Yukarı al') : __('Aşağı al') }}"
                                        class="grid size-7 place-items-center rounded-lg text-slate-400 ring-1 ring-slate-200 transition hover:bg-white hover:text-brand-900 disabled:opacity-30">
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}" /></svg>
                                    </button>
                                </form>
                            @endforeach
                        </div>

                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-rose-50 text-rose-600">{!! $feature->iconHtml() !!}</span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900">{{ $feature->title }}</p>
                                @if ($feature->otherLocales())
                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600" title="{{ strtoupper(implode(', ', $feature->otherLocales())) }}">+{{ count($feature->otherLocales()) }} dil</span>
                                @endif
                            </div>
                            <p class="mt-0.5 line-clamp-1 text-sm text-slate-600">{{ $feature->text }}</p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <x-admin.toggle :action="route('features.toggle', $feature)" :active="$feature->active" :label="__('Yayında')" />
                            <a href="{{ route('features.edit', $feature) }}" title="{{ __('Düzenle') }}" aria-label="{{ __('Düzenle') }}"
                                class="ml-2 grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-brand-900">
                                <x-admin.icon name="pencil" class="size-[18px]" />
                            </a>
                            <form action="{{ route('features.destroy', $feature) }}" method="POST" data-confirm="{{ __('Bu kart silinsin mi?') }}">
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
                {{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $features->count() }}</strong> {{ __('kart') }},
                <strong class="font-semibold text-slate-700">{{ $features->where('active', true)->count() }}</strong> {{ __('yayında') }}.
                {{ __('Oklarla sırasını değiştir; anasayfada bu sırayla görünür.') }}
            </div>
        @endif
    </x-admin.card>
@endsection

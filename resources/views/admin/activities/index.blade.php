@extends('layouts.admin')

@section('title', 'Aktiviteler')

@section('header')
    <x-admin.page-header :title="__('Aktiviteler')" description="{{ $activeCount }} yayında · toplam {{ $totalCount }} aktivite">
        <x-slot:icon><path d="M4 7h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4Z" /><path d="M14 7v12" stroke-dasharray="2 2" /></x-slot:icon>
        <a href="{{ route('activities.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni aktivite') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.card class="overflow-hidden">
        <x-admin.filter-bar :search="$search" placeholder="Aktivite adı ya da URL ara…"
            :tabs="['name' => 'status', 'value' => $status, 'options' => ['' => ['Tümü', $totalCount], 'active' => ['Yayında', $activeCount], 'passive' => ['Pasif', $totalCount - $activeCount]]]"
            :filters="[
                ['name' => 'city', 'label' => 'Şehir', 'value' => $cityId, 'options' => $filterCities->mapWithKeys(fn ($c) => [$c->id => $c->country->flag . ' ' . $c->name])->all(), 'icon' => 'pin'],
                ['name' => 'partner', 'label' => 'Partner', 'value' => $partnerId, 'options' => $filterPartners->all(), 'icon' => 'link'],
                ['name' => 'type', 'label' => 'Tür', 'value' => $type?->value, 'options' => collect(\App\Enums\ActivityType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all(), 'icon' => 'ticket'],
                ['name' => 'popular', 'label' => 'Popüler', 'value' => $popular, 'options' => ['yes' => __('Popüler olanlar'), 'no' => __('Popüler olmayanlar')], 'icon' => 'star'],
            ]" />

        @if ($activities->isEmpty())
            <div class="border-t border-slate-100 px-6 py-16 text-center">
                <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-900"><x-admin.icon name="search" class="size-6" /></div>
                <p class="mt-4 text-sm font-semibold text-slate-900">{{ __('Aktivite bulunamadı') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('Filtreyi değiştir ya da yeni bir aktivite ekle.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-slate-100 bg-slate-50/60 text-xs text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Aktivite') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Şehir') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Partner') }}</th>
                            <th class="px-6 py-3 text-right font-medium">{{ __('Tıklama (30 gün)') }}</th>
                            <th class="px-6 py-3 text-center font-medium">{{ __('Popüler') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Yayında') }}</th>
                            <th class="px-6 py-3"><span class="sr-only">{{ __('İşlemler') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($activities as $activity)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3.5">
                                        @if ($url = $activity->cover?->url('sm'))
                                            <img src="{{ $url }}" alt="" loading="lazy" class="h-12 w-16 shrink-0 rounded-lg object-cover ring-1 ring-slate-200" />
                                        @else
                                            <span class="grid h-12 w-16 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200" title="{{ __('Görsel yok') }}">
                                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="9" cy="10" r="2" /><path d="m21 16-5-5L5 20" /></svg>
                                            </span>
                                        @endif
                                        <div class="min-w-0 max-w-md">
                                            <a href="{{ route('activities.edit', $activity) }}" class="line-clamp-1 font-semibold text-slate-900 hover:text-brand-900 hover:underline">{{ $activity->name }}</a>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                                <span class="rounded-md bg-slate-100 px-1.5 py-0.5 font-medium text-slate-600">{{ $activity->type->label() }}</span>
                                                @if ($activity->otherLocales())
                                                    <span class="rounded-md bg-brand-50 px-1.5 py-0.5 font-medium text-brand-900" title="{{ strtoupper(implode(', ', $activity->otherLocales())) }}">+{{ count($activity->otherLocales()) }} dil</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-slate-700">
                                    <span class="whitespace-nowrap">{{ $activity->city->country->flag }} {{ $activity->city->name }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    @if ($activity->partner)
                                        <span class="flex items-center gap-2 whitespace-nowrap text-slate-700"><x-admin.partner-logo :partner="$activity->partner" class="size-7" /> {{ $activity->partner->name }}</span>
                                    @else
                                        <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700">{{ __('Partner yok') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right font-semibold tabular-nums text-slate-900">{{ number_format($clicks[$activity->id] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-center">
                                    <x-admin.toggle variant="star" :action="route('activities.popular', $activity)" :active="$activity->is_popular" :label="__('Popüler (anasayfada öne çıkar)')" />
                                </td>
                                <td class="px-6 py-3">
                                    <x-admin.toggle :action="route('activities.toggle', $activity)" :active="$activity->active" :label="__(':name yayında', ['name' => $activity->name])" />
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('activities.edit', $activity) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-100 hover:ring-blue-200">
                                            <x-admin.icon name="pencil" /> <span class="hidden xl:inline">{{ __('Düzenle') }}</span>
                                        </a>
                                        <form action="{{ route('activities.destroy', $activity) }}" method="POST" data-confirm="{{ __('":name" silinsin mi? Görselleriyle birlikte silinir.', ['name' => $activity->name]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="{{ __('Sil') }}" aria-label="{{ $activity->name }} sil"
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
                <span>{{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $activities->total() }}</strong> {{ __('aktivite') }}</span>
                @if ($activities->hasPages()) {{ $activities->links() }} @endif
            </div>
        @endif
    </x-admin.card>
@endsection

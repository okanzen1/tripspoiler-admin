@extends('layouts.admin')

@section('title', 'Sıkça sorulan sorular')

@section('header')
    <x-admin.page-header :title="__('Sıkça sorulan sorular')" :description="__('Her sorunun sitede nerede çıktığı yanındaki etikette yazar: anasayfa, şehre göre değişen sayfalar (Aktiviteler, Şehirler, Blog), bir aktivite, bir blog yazısı ya da diğer sayfalar.')">
        <x-slot:icon><circle cx="12" cy="12" r="9" /><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h.01" /></x-slot:icon>
        <a href="{{ route('faqs.texts') }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-white">
            {{ __('Bölüm yazıları') }}
        </a>
        <a href="{{ route('faqs.create', ['bulk' => 1]) }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-900 ring-1 ring-brand-200 transition hover:bg-brand-50">
            <x-admin.icon name="plus" /> {{ __('Toplu ekle') }}
        </a>
        <a href="{{ route('faqs.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni soru') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.todo-list :types="['faq']" />
    <x-admin.card class="overflow-hidden">
        <x-admin.filter-bar :search="$search" placeholder="Soru ya da cevapta ara…"
            :tabs="['name' => 'scope', 'value' => $scope, 'options' => ['' => ['Tümü', array_sum($counts)], 'general' => ['Anasayfa', $counts['general']], 'city' => ['Aktiviteler · Şehirler · Blog', $counts['city']], 'activity' => ['Aktivite sayfası', $counts['activity']], 'blog' => ['Blog yazısı', $counts['blog']], 'other' => ['Diğer sayfalar', $counts['other']]]]"
            :filters="array_values(array_filter([
                $cities->isNotEmpty() && in_array($scope, [null, 'city'], true) ? ['name' => 'city', 'label' => 'Şehir', 'value' => $cityId, 'options' => $cities->pluck('name', 'id')->all(), 'icon' => 'pin'] : null,
                $activities->isNotEmpty() && in_array($scope, [null, 'activity'], true) ? ['name' => 'activity', 'label' => 'Aktivite', 'value' => $activityId, 'options' => $activities->mapWithKeys(fn ($a) => [$a->id => \Illuminate\Support\Str::limit($a->name, 45)])->all(), 'icon' => 'ticket'] : null,
                $blogs->isNotEmpty() && in_array($scope, [null, 'blog'], true) ? ['name' => 'blog', 'label' => 'Blog', 'value' => $blogId, 'options' => $blogs->mapWithKeys(fn ($b) => [$b->id => \Illuminate\Support\Str::limit($b->title, 45)])->all(), 'icon' => 'pen'] : null,
                ['name' => 'status', 'label' => 'Durum', 'value' => $status, 'options' => ['active' => __('Yayında'), 'passive' => __('Pasif')], 'icon' => 'eye'],
            ]))" />

        @if ($faqs->isEmpty())
            <div class="border-t border-slate-100 px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ __('Soru bulunamadı') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('Filtreyi değiştir ya da yeni bir soru ekle.') }}</p>
            </div>
        @else
            <div class="flex items-center gap-2 border-t border-slate-100 bg-slate-50/60 px-6 py-2.5 text-xs text-slate-500">
                <svg class="size-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" /><circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" /><circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" /></svg>
                <span>{{ __('Sırayı değiştirmek için soldaki tutamaçtan tutup sürükle. Sitede bu sırayla görünür; her soru yalnızca kendi yerindekilerle (aynı etiket) yer değiştirir.') }}</span>
                <span data-order-saved="faqs" class="ml-auto hidden font-semibold text-emerald-600">✓ {{ __('Sıra kaydedildi') }}</span>
            </div>
            <ul class="divide-y divide-slate-100 border-t border-slate-100" data-sortable-grid data-sortable-list data-order-key="faqs" data-order-url="{{ route('faqs.reorder') }}">
                @foreach ($faqs as $faq)
                    <li class="flex items-start gap-3 py-4 pl-2 pr-6 transition hover:bg-slate-50/60" data-card="{{ $faq->id }}" data-group="{{ $faq->activity_id }}|{{ $faq->blog_id }}|{{ $faq->city_id }}|{{ $faq->page_key }}">
                        <span data-drag-handle draggable="true" title="{{ __('Sürükleyerek sırala') }}" aria-label="{{ __('Sürükleyerek sırala') }}"
                            class="mt-6 grid h-10 w-8 shrink-0 cursor-grab place-items-center rounded-lg text-slate-300 transition hover:bg-slate-100 hover:text-slate-600 active:cursor-grabbing">
                            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" /><circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" /><circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($faq->activity)
                                    <a href="{{ route('activities.edit', $faq->activity) }}" class="max-w-80 truncate rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-900 hover:underline" title="{{ $faq->activity->name }}">{{ $faq->activity->name }}</a>
                                @elseif ($faq->page_key && $faq->city)
                                    <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700">{{ $faq->page?->name ?? $faq->page_key }} · {{ $faq->city->name }}</span>
                                @elseif ($faq->city)
                                    <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700">{{ __('Aktiviteler · Şehirler · Blog') }} · {{ $faq->city->name }}</span>
                                @elseif ($faq->page_key)
                                    <span @class(['rounded-md px-2 py-0.5 text-xs font-medium', 'bg-sky-50 text-sky-700' => in_array($faq->page_key, ['activities', 'cities', 'blog'], true), 'bg-amber-50 text-amber-800' => !in_array($faq->page_key, ['activities', 'cities', 'blog'], true)])>{{ $faq->page?->name ?? $faq->page_key }}{{ in_array($faq->page_key, ['activities', 'cities', 'blog'], true) ? ' · ' . __('bütün şehirler') : '' }}</span>
                                @elseif ($faq->blog)
                                    <a href="{{ route('blogs.edit', $faq->blog) }}" class="max-w-80 truncate rounded-md bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700 hover:underline" title="{{ $faq->blog->title }}">Blog · {{ $faq->blog->title }}</a>
                                @else
                                    <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('Anasayfa') }}</span>
                                @endif
                                @if ($faq->otherLocales())
                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600" title="{{ strtoupper(implode(', ', $faq->otherLocales())) }}">+{{ count($faq->otherLocales()) }} dil</span>
                                @endif
                            </div>
                            <p class="mt-1.5 font-semibold text-slate-900">{{ $faq->question }} <x-admin.todo-pill type="faq" :id="$faq->id" class="ml-1 align-middle" /></p>
                            <p class="mt-0.5 line-clamp-2 text-sm text-slate-600">{{ $faq->answer }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5 pt-1">
                            <x-admin.toggle :action="route('faqs.toggle', $faq)" :active="$faq->active" :label="__('Yayında')" />
                            <a href="{{ route('faqs.edit', $faq) }}" title="{{ __('Düzenle') }}" aria-label="{{ __('Düzenle') }}"
                                class="ml-2 grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-brand-900">
                                <x-admin.icon name="pencil" class="size-[18px]" />
                            </a>
                            <form action="{{ route('faqs.destroy', $faq) }}" method="POST" data-confirm="{{ __('Bu soru silinsin mi?') }}">
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
            <div class="flex items-center justify-between border-t border-slate-100 px-6 py-3.5 text-sm text-slate-500">
                <span>{{ __('Toplam') }} <strong class="font-semibold text-slate-700">{{ $faqs->total() }}</strong> {{ __('soru') }}</span>
                @if ($faqs->hasPages()) {{ $faqs->links() }} @endif
            </div>
        @endif
    </x-admin.card>
@endsection

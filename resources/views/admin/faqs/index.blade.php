@extends('layouts.admin')

@section('title', 'Sıkça sorulan sorular')

@section('header')
    <x-admin.page-header :title="__('Sıkça sorulan sorular')" :description="__('Her sorunun sitede nerede çıktığı yanındaki etikette yazar: anasayfa, bir sayfa, bir şehir, bir aktivite ya da blog yazısı.')">
        <x-slot:icon><circle cx="12" cy="12" r="9" /><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h.01" /></x-slot:icon>
        <a href="{{ route('faqs.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <x-admin.icon name="plus" /> {{ __('Yeni soru') }}
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <x-admin.card class="overflow-hidden">
        <x-admin.filter-bar :search="$search" placeholder="Soru ya da cevapta ara…"
            :tabs="['name' => 'scope', 'value' => $scope, 'options' => ['' => ['Tümü', array_sum($counts)], 'general' => ['Anasayfa', $counts['general']], 'page' => ['Sayfa', $counts['page']], 'city' => ['Şehir', $counts['city']], 'activity' => ['Aktivite', $counts['activity']], 'blog' => ['Blog', $counts['blog']]]]"
            :filters="array_values(array_filter([
                $activities->isNotEmpty() ? ['name' => 'activity', 'label' => 'Aktivite', 'value' => $activityId, 'options' => $activities->mapWithKeys(fn ($a) => [$a->id => \Illuminate\Support\Str::limit($a->name, 45)])->all(), 'icon' => 'ticket'] : null,
                $blogs->isNotEmpty() ? ['name' => 'blog', 'label' => 'Blog', 'value' => $blogId, 'options' => $blogs->mapWithKeys(fn ($b) => [$b->id => \Illuminate\Support\Str::limit($b->title, 45)])->all(), 'icon' => 'pen'] : null,
                ['name' => 'status', 'label' => 'Durum', 'value' => $status, 'options' => ['active' => __('Yayında'), 'passive' => __('Pasif')], 'icon' => 'eye'],
            ]))" />

        @if ($faqs->isEmpty())
            <div class="border-t border-slate-100 px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ __('Soru bulunamadı') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('Filtreyi değiştir ya da yeni bir soru ekle.') }}</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100 border-t border-slate-100">
                @foreach ($faqs as $faq)
                    <li class="flex items-start gap-4 px-6 py-4 transition hover:bg-slate-50/60">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($faq->activity)
                                    <a href="{{ route('activities.edit', $faq->activity) }}" class="max-w-80 truncate rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-900 hover:underline" title="{{ $faq->activity->name }}">{{ $faq->activity->name }}</a>
                                @elseif ($faq->city)
                                    <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700">{{ __('Şehir') }} · {{ $faq->city->name }}</span>
                                @elseif ($faq->page_key)
                                    <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">{{ __('Sayfa') }} · {{ $faq->page?->name ?? $faq->page_key }}</span>
                                @elseif ($faq->blog)
                                    <a href="{{ route('blogs.edit', $faq->blog) }}" class="max-w-80 truncate rounded-md bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700 hover:underline" title="{{ $faq->blog->title }}">Blog · {{ $faq->blog->title }}</a>
                                @else
                                    <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('Anasayfa') }}</span>
                                @endif
                                @if ($faq->otherLocales())
                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600" title="{{ strtoupper(implode(', ', $faq->otherLocales())) }}">+{{ count($faq->otherLocales()) }} dil</span>
                                @endif
                            </div>
                            <p class="mt-1.5 font-semibold text-slate-900">{{ $faq->question }}</p>
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

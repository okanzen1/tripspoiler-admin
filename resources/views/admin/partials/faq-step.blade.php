{{--
    Sihirbazdaki SSS adımı (aktivite, blog). $rows: [['id' => ?, 'question' => ?, 'answer' => ?], …] · $description
--}}
<section data-step hidden class="space-y-5">
    <x-admin.step-heading :title="__('Sıkça sorulan sorular')" :description="$description" />

    <div data-repeater="faqs" data-max="{{ $max ?? 30 }}" data-remove-confirm="{{ __('Bu soru silinsin mi? Kaydettiğinde kalıcı olarak silinir.') }}" class="space-y-3">
        <div data-repeater-list class="space-y-3">
            @foreach ($rows as $row)
                @include('admin.partials.faq-row', ['faq' => $row, 'number' => $loop->iteration])
            @endforeach
        </div>
        <p data-repeater-empty class="hidden rounded-2xl border-2 border-dashed border-slate-200 px-6 py-8 text-center text-sm text-slate-500">{{ __('Henüz soru yok.') }}</p>
        <template data-repeater-template>
            @include('admin.partials.faq-row', ['faq' => []])
        </template>
        <button type="button" data-repeater-add
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-900 ring-1 ring-brand-200 transition hover:bg-brand-50 disabled:opacity-40">
            <x-admin.icon name="plus" /> {{ __('Soru ekle') }}
        </button>
    </div>
    @foreach ($errors->get('faqs*') as $messages)
        <p class="text-xs font-medium text-red-600">{{ $messages[0] }}</p>
    @endforeach
</section>

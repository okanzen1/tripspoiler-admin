{{-- Bölüm yazısının çeviri durumu: dil hapları (elle düzeltme) + çevir butonu (form="$formId") --}}
<div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl px-5 py-4 ring-1 ring-slate-200">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-semibold text-slate-900">{{ __('Diğer diller') }}</span>
        @foreach (\App\Models\Language::targets() as $language)
            @php $state = $status[$language->code] ?? 'missing'; @endphp
            <a href="{{ route('translations.edit', ['section', $model->id, $language->code]) }}" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-slate-200 hover:bg-slate-50">
                <span class="size-2 rounded-full {{ ['ok' => 'bg-emerald-500', 'stale' => 'bg-amber-500'][$state] ?? 'bg-slate-300' }}"></span>{{ $language->native_name }}
            </a>
        @endforeach
    </div>
    @if ($pending)
        <button type="submit" form="{{ $formId }}" class="rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700">{{ __('Diğer dillere çevir (:count)', ['count' => $pending]) }}</button>
    @else
        <span class="rounded-xl bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-700">✓ {{ __('Tüm diller güncel') }}</span>
    @endif
</div>

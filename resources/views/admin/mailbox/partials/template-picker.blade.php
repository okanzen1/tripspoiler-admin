{{-- Hazır yanıt seçici: seçilen metin, $target kimlikli metin alanında imlecin olduğu yere eklenir (resources/js/mailbox.js) --}}
@if ($templates->isNotEmpty())
    <select data-template-picker="{{ $target }}" data-templates='@json($templates->pluck('body', 'id'))' aria-label="{{ __('Hazır yanıt ekle') }}"
        class="rounded-lg border-0 py-1.5 pl-3 pr-8 text-xs font-medium text-brand-900 ring-1 ring-slate-200">
        <option value="">{{ __('Hazır yanıt ekle…') }}</option>
        @foreach ($templates as $template)
            <option value="{{ $template->id }}">{{ $template->title }}</option>
        @endforeach
    </select>
@else
    <a href="{{ route('mailbox.templates.index') }}" class="text-xs font-medium text-brand-900 hover:underline">{{ __('Hazır yanıt oluştur') }}</a>
@endif

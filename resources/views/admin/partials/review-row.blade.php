{{-- Aktivite sihirbazında yorum satırı. Alan adları JS ile numaralanır (resources/js/repeater.js).
     $review: ['id' => ?, 'name' => ?, 'rating' => ?, 'comment' => ?, 'approved' => ?, 'date' => ?] --}}
@php($input = 'block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-900 focus:ring-4 focus:ring-brand-900/10')
@php($approved = (bool) ($review['approved'] ?? true))

<div data-repeater-row class="rounded-2xl bg-white p-4 ring-1 ring-slate-200 has-[[type=checkbox][data-field=approved]:not(:checked)]:bg-amber-50/50 has-[[type=checkbox][data-field=approved]:not(:checked)]:ring-amber-200">
    <input type="hidden" data-field="id" value="{{ $review['id'] ?? '' }}" />
    <div class="flex items-start gap-3">
        <span class="mt-1 grid size-7 shrink-0 place-items-center rounded-full bg-brand-50 text-xs font-semibold text-brand-900" data-repeater-number></span>
        <div class="min-w-0 flex-1 space-y-3">
            <div class="flex flex-wrap items-center gap-3">
                <input type="text" data-field="name" value="{{ $review['name'] ?? '' }}" maxlength="120" placeholder="{{ __('Ad soyad') }}" aria-label="{{ __('Ad soyad') }}"
                    class="{{ $input }} min-w-48 flex-1 font-medium" />
                <select data-field="rating" aria-label="{{ __('Puan') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-amber-500 outline-none focus:border-brand-900">
                    @for ($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" @selected((int) ($review['rating'] ?? 5) === $i)>{{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }}</option>
                    @endfor
                </select>
                <label class="flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 ring-1 ring-slate-200">
                    <input type="hidden" data-field="approved" value="0" />
                    <input type="checkbox" data-field="approved" value="1" class="size-4 accent-brand-900" @checked($approved) /> {{ __('Onaylı') }}
                </label>
                @if (!empty($review['date']))
                    <span class="text-xs text-slate-400">{{ $review['date'] }}</span>
                @endif
            </div>
            <textarea data-field="comment" rows="3" maxlength="2000" dir="auto" placeholder="{{ __('Yorum') }}" aria-label="{{ __('Yorum') }}" class="{{ $input }} resize-y leading-relaxed">{{ $review['comment'] ?? '' }}</textarea>
        </div>
        <div class="flex shrink-0 flex-col gap-1">
            {{-- Yorumların sırası tarihe göredir; taşıma düğmeleri gizli ama satır yapısı için gerekli --}}
            <button type="button" data-repeater-up class="hidden" tabindex="-1"></button>
            <button type="button" data-repeater-down class="hidden" tabindex="-1"></button>
            <button type="button" data-repeater-remove title="{{ __('Sil') }}" aria-label="{{ __('Sil') }}" class="grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                <x-admin.icon name="trash" />
            </button>
        </div>
    </div>
</div>

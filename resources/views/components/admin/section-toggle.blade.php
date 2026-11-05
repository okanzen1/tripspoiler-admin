@props(['section'])

{{-- Bölümü sitede aç/kapa (App\Support\SiteSections). Kapalı bölüm sitenin hiçbir sayfasında görünmez. --}}
@php $on = \App\Support\SiteSections::enabled($section); @endphp
<div {{ $attributes->class('inline-flex items-center gap-2.5 rounded-full bg-white py-1.5 pl-3.5 pr-1.5 shadow-sm ring-1 ring-slate-200') }} data-section-toggle>
    <span class="text-sm font-semibold {{ $on ? 'text-emerald-700' : 'text-slate-500' }}" data-section-state
        data-on="{{ __('Sitede yayında') }}" data-off="{{ __('Sitede gizli') }}">{{ $on ? __('Sitede yayında') : __('Sitede gizli') }}</span>
    <x-admin.toggle :action="route('site-settings.section', $section)" :active="$on" :label="__('Bölüm yayında')" />
</div>

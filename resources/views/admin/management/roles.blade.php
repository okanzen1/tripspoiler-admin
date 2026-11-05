@extends('layouts.admin')

@section('title', 'Roller')

@section('header')
    <x-admin.page-header :title="__('Roller')" :description="__('Paneldeki roller ve erişebildikleri alanlar.')" :breadcrumb="['Yönetim' => route('management.index')]">
        <x-slot:icon><path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3Z" /><path d="m9 12 2 2 4-4" /></x-slot:icon>
    </x-admin.page-header>
@endsection

@section('content')
    <div class="grid gap-5 md:grid-cols-2">
        @foreach (\App\Enums\Role::cases() as $role)
            <x-admin.card class="p-6">
                <div class="flex items-center justify-between">
                    <x-admin.role-badge :role="$role" />
                    <a href="{{ route('users.index', ['role' => $role->value]) }}" class="text-sm font-medium text-slate-500 transition hover:text-brand-700">
                        {{ $counts[$role->value] ?? 0 }} kullanıcı →
                    </a>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ $role->label() }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $role->description() }}</p>

                <ul class="mt-5 space-y-2.5 border-t border-slate-100 pt-5 text-sm">
                    @foreach ($role->abilities() as $ability)
                        <li class="flex items-center gap-2.5 text-slate-700">
                            <span class="grid size-5 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-9" /></svg>
                            </span>
                            {{ $ability }}
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>
        @endforeach
    </div>
@endsection

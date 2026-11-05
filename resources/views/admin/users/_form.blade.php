@php($isSelf = $user->is(auth()->user()))

<x-admin.card class="overflow-hidden">
    <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="divide-y divide-slate-100">
            <section class="grid gap-6 p-6 sm:p-8 md:grid-cols-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Kişisel bilgiler') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Profil resmi, panelde görünen ad ve giriş e-postası.') }}</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 md:col-span-2">
                    <div class="sm:col-span-2">
                        <x-admin.image-upload name="avatar" :label="__('Profil resmi')" shape="circle" :current="$user->avatarUrl()" :placeholder="$user->initials" />
                    </div>
                    <x-admin.input name="first_name" :label="__('Ad')" :value="$user->first_name" required />
                    <x-admin.input name="last_name" :label="__('Soyad')" :value="$user->last_name" required />
                    <div class="sm:col-span-2">
                        <x-admin.input name="email" :label="__('E-posta')" type="email" :value="$user->email" required autocomplete="off" />
                    </div>
                </div>
            </section>

            <section class="grid gap-6 p-6 sm:p-8 md:grid-cols-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Rol ve dil') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ __($isSelf ? 'Kendi rolünü değiştiremezsin.' : 'Kullanıcının panelde neleri yapabileceği.') }}
                    </p>
                </div>
                <div class="md:col-span-2">
                    @include('admin.users._role-picker', ['selected' => old('role', $user->role->value), 'disabled' => $isSelf])
                    @if ($isSelf)
                        <input type="hidden" name="role" value="{{ $user->role->value }}" />
                    @endif
                    <div class="mt-6">@include('admin.users._locale-picker', ['selected' => $user->locale ?? 'tr'])</div>
                </div>
            </section>

            <section class="grid gap-6 p-6 sm:p-8 md:grid-cols-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Şifre') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Değiştirmek istemiyorsan boş bırak.') }}</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 md:col-span-2">
                    <x-admin.input name="password" :label="__('Yeni şifre')" type="password" autocomplete="new-password" />
                    <x-admin.input name="password_confirmation" :label="__('Yeni şifre (tekrar)')" type="password" autocomplete="new-password" />
                </div>
            </section>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:px-8">
            <a href="{{ route('users.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">{{ __('Vazgeç') }}</a>
            <button type="submit" class="rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                {{ __('Değişiklikleri kaydet') }}
            </button>
        </div>
    </form>
</x-admin.card>

{{--
    Claude uygulamasına panel erişim izni (OAuth onay ekranı; Passport::authorizationView, AppServiceProvider).
    Claude'da "Bağlayıcı ekle" deyince bu sayfa açılır; yalnızca admin onay verebilir.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Claude erişim izni') }} · TripSpoiler</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-full place-items-center bg-[#e9edf4] p-4 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-slate-200">
        <div class="bg-brand-900 px-7 py-6 text-white">
            <div class="flex items-center gap-3">
                <x-admin.logo class="size-11" />
                <div>
                    <p class="text-lg font-semibold">{{ __('Claude erişim izni') }}</p>
                    <p class="text-sm text-white/70">{{ __('TripSpoiler yönetim paneli') }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-5 px-7 py-6">
            <p class="text-sm leading-relaxed text-slate-600">
                <strong class="text-slate-900">{{ $client->name }}</strong>
                {{ __('panelde senin hesabınla işlem yapmak istiyor. İzin verirsen Claude, sohbette ondan istediğin her şeyi panelde yapabilir: aktivite, blog, yorum, SSS, şehir, sayfa ve ayarları ekleyip düzenleyebilir, silebilir.') }}
            </p>

            <ul class="space-y-2 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200">
                <li class="flex gap-2"><span class="text-emerald-600">✓</span>{{ __('Sadece sen ne dersen onu yapar; kendiliğinden bir şey yapmaz.') }}</li>
                <li class="flex gap-2"><span class="text-emerald-600">✓</span>{{ __('Silmeden önce sana sorar.') }}</li>
                <li class="flex gap-2"><span class="text-emerald-600">✓</span>{{ __('Yaptığı her şey Yönetim › İşlem kaydı\'nda senin adınla görünür.') }}</li>
                <li class="flex gap-2"><span class="text-emerald-600">✓</span>{{ __('İzni istediğin zaman Claude\'da bağlayıcıyı kaldırarak geri alırsın.') }}</li>
            </ul>

            <div class="flex items-center gap-3 rounded-2xl p-3 ring-1 ring-slate-200">
                <x-admin.avatar :user="$user" class="size-10" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $user->full_name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                </div>
            </div>

            @if ($user->isAdmin())
                <div class="flex gap-3">
                    <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-1" data-no-ajax>
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="state" value="">
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button type="submit" class="w-full rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">{{ __('Vazgeç') }}</button>
                    </form>
                    <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-1" data-no-ajax>
                        @csrf
                        <input type="hidden" name="state" value="">
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button type="submit" class="w-full rounded-xl bg-brand-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">{{ __('İzin ver') }}</button>
                    </form>
                </div>
            @else
                <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 ring-1 ring-red-200">{{ __('Claude\'u panele yalnızca admin bağlayabilir. Bu hesap admin değil.') }}</p>
            @endif
        </div>
    </div>
</body>
</html>

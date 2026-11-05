<!doctype html>
<html lang="tr" class="h-full">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ __('Abonelik · TripSpoiler') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css'])
</head>

<body class="grid h-full place-items-center bg-[#e9edf4] px-4 font-sans text-slate-800 antialiased">
    <main class="w-full max-w-md rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200">
        <x-admin.logo :size="512" class="mx-auto size-24" />

        @if ($done)
            <h1 class="mt-5 text-xl font-semibold text-slate-900">{{ __('Aboneliğin iptal edildi') }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ $subscriber->email }} adresine artık blog e-postası gönderilmeyecek.</p>
        @else
            <h1 class="mt-5 text-xl font-semibold text-slate-900">{{ __('Abonelikten çıkılsın mı?') }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ $subscriber->email }} adresine TripSpoiler blog e-postaları gönderilmeyecek.</p>
            <form method="POST" action="{{ URL::signedRoute('subscribers.unsubscribe.confirm', ['subscriber' => $subscriber->id]) }}" class="mt-6">
                @csrf
                <button class="w-full rounded-xl bg-brand-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">{{ __('Evet, abonelikten çık') }}</button>
            </form>
        @endif
    </main>
</body>

</html>

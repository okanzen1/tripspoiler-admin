<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="color-scheme" content="light" />
    <title>{{ __('Giriş · TripSpoiler Admin') }}</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-[#e9edf4] font-sans text-slate-900 antialiased">

    <main class="flex min-h-full">

        {{-- Sol: marka paneli --}}
        <section class="relative hidden w-1/2 overflow-hidden bg-brand-900 text-white lg:flex lg:flex-col lg:justify-between lg:p-12">

            {{-- Rota deseni --}}
            <svg class="pointer-events-none absolute inset-0 h-full w-full text-white/[0.07]" viewBox="0 0 600 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                <defs>
                    <pattern id="dots" width="24" height="24" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1.2" fill="currentColor" />
                    </pattern>
                </defs>
                <rect width="600" height="800" fill="url(#dots)" />
                <path d="M40 760 C 140 640, 60 520, 200 470 S 460 420, 420 260 S 520 90, 580 40"
                    fill="none" stroke="white" stroke-opacity="0.25" stroke-width="2" stroke-dasharray="6 10" stroke-linecap="round" />
            </svg>

            <div class="relative flex items-center gap-2">
                <span class="text-xl font-semibold tracking-tight">{{ __('TripSpoiler') }}</span>
                <span class="rounded-md bg-white px-1.5 py-0.5 text-xs font-semibold text-brand-900">{{ __('Admin') }}</span>
            </div>

            <x-admin.logo :size="512" class="relative mx-auto size-80 drop-shadow-2xl xl:size-96" />

            <div class="relative max-w-md">
                <h1 class="text-3xl font-semibold leading-tight tracking-tight">
                    {{ __('Şehirleri, aktiviteleri ve hikâyeleri tek yerden yönet.') }}
                </h1>
                <p class="mt-3 text-base leading-relaxed text-white/60">
                    {{ __('TripSpoiler\'ın tüm içeriği bu panelde.') }}
                </p>
            </div>
        </section>

        {{-- Sağ: form --}}
        <section class="relative flex w-full items-center justify-center px-4 py-12 sm:px-6 lg:w-1/2">
            <div class="absolute right-4 top-4 sm:right-6 sm:top-6"><x-admin.locale-switch /></div>
            <div class="w-full max-w-sm">

                {{-- Mobil logo --}}
                <div class="mb-8 flex flex-col items-center gap-3 lg:hidden">
                    <x-admin.logo class="size-28" />
                    <span class="text-lg font-semibold tracking-tight">{{ __('TripSpoiler') }} <span class="text-brand-600">{{ __('Admin') }}</span></span>
                </div>

                <h2 class="text-2xl font-semibold tracking-tight">{{ __('Tekrar hoş geldin') }}</h2>
                <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">{{ __('Yönetim paneline erişmek için giriş yap.') }}</p>

                @if ($errors->any())
                    <div role="alert" class="mt-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/50 dark:text-red-300">
                        <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form id="login-form" action="{{ route('login.submit') }}" method="POST" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium">{{ __('E-posta') }}</label>
                        <div class="relative mt-2">
                            <svg class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2" />
                                <path d="m4 7 8 6 8-6" />
                            </svg>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                autocomplete="username" required autofocus
                                class="block w-full rounded-lg border border-stone-300 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm outline-none transition placeholder:text-stone-400 focus:border-brand-600 focus:ring-4 focus:ring-brand-600/15 dark:border-stone-700 dark:bg-stone-900 dark:focus:border-brand-500"
                                placeholder="{{ __('ornek@tripspoiler.com') }}" />
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium">{{ __('Şifre') }}</label>
                        <div class="relative mt-2">
                            <svg class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                <rect x="5" y="11" width="14" height="9" rx="2" />
                                <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                            </svg>
                            <input id="password" type="password" name="password" autocomplete="current-password" required
                                class="block w-full rounded-lg border border-stone-300 bg-white py-2.5 pl-10 pr-11 text-sm shadow-sm outline-none transition placeholder:text-stone-400 focus:border-brand-600 focus:ring-4 focus:ring-brand-600/15 dark:border-stone-700 dark:bg-stone-900 dark:focus:border-brand-500"
                                placeholder="••••••••" />
                            <button type="button" id="toggle-password" aria-label="{{ __('Şifreyi göster') }}"
                                class="absolute right-2 top-1/2 grid size-8 -translate-y-1/2 place-items-center rounded-md text-stone-400 transition hover:text-stone-600 focus-visible:outline-2 focus-visible:outline-brand-600 dark:hover:text-stone-200">
                                <svg data-eye class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg data-eye-off class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 3l18 18M10.6 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.2M6.6 6.6C3.8 8.4 2 12 2 12s3.5 7 10 7c1.8 0 3.4-.5 4.8-1.3M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="login-submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-800 disabled:cursor-wait disabled:opacity-75 dark:bg-brand-600 dark:hover:bg-brand-500">
                        <svg data-spinner class="hidden size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.3" stroke-width="3" />
                            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                        <span data-label>{{ __('Giriş yap') }}</span>
                    </button>
                </form>

                <p class="mt-10 text-center text-xs text-stone-400 lg:hidden">© {{ date('Y') }} TripSpoiler</p>
            </div>
        </section>
    </main>

    <script>
        const password = document.getElementById('password');
        const toggle = document.getElementById('toggle-password');

        toggle.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-label', show ? @json(__('Şifreyi gizle')) : @json(__('Şifreyi göster')));
            toggle.querySelector('[data-eye]').classList.toggle('hidden', show);
            toggle.querySelector('[data-eye-off]').classList.toggle('hidden', !show);
        });

        document.getElementById('login-form').addEventListener('submit', () => {
            const button = document.getElementById('login-submit');
            button.disabled = true;
            button.querySelector('[data-spinner]').classList.remove('hidden');
            button.querySelector('[data-label]').textContent = @json(__('Giriş yapılıyor…'));
        });
    </script>

</body>

</html>

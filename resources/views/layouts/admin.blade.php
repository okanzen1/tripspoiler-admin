<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Admin')</title>

    <link rel="stylesheet" href="{{ asset('adminkit/css/custom.css') }}">
    <link href="{{ asset('adminkit/css/app.css') }}" rel="stylesheet" />
</head>

<body>

    <div class="wrapper">

        @include('admin.partials.sidebar')

        <div class="main">

            @include('admin.partials.navbar')

            <main class="content">
                <div class="container-fluid p-0">
                    @yield('content')
                </div>
            </main>

        </div>

    </div>

    <script src="{{ asset('adminkit/js/app.js') }}"></script>
</body>

</html>

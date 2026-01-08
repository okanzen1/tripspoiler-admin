<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Admin')</title>

    <link href="{{ asset('adminkit/css/app.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('adminkit/css/custom.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css">
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
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    @yield('scripts')

</body>

</html>

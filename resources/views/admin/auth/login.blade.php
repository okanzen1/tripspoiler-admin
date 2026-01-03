<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="{{ asset('adminkit/css/app.css') }}" rel="stylesheet" />
</head>

<body>

    <main class="d-flex w-100">
        <div class="container d-flex flex-column">
            <div class="row vh-100">
                <div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
                    <div class="d-table-cell align-middle">

                        <div class="text-center mt-4">
                            <h1 class="h2 fw-bold">TripSpoiler Admin</h1>
                            <p class="lead">
                                Yönetim paneline giriş yapın
                            </p>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="m-sm-3">

                                    {{-- Hata mesajı --}}
                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            {{ $errors->first() }}
                                        </div>
                                    @endif

                                    <form action="{{ route('login.submit') }}" method="POST">
                                        @csrf

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Kullanıcı Adı</label>
                                            <input type="text" name="username"
                                                class="form-control form-control-lg" required autofocus />
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Şifre</label>
                                            <input type="password" name="password"
                                                class="form-control form-control-lg" required />
                                        </div>

                                        <div class="text-center mt-3">
                                            <button type="submit" class="btn btn-lg btn-primary w-100">
                                                Giriş Yap
                                            </button>
                                        </div>

                                    </form>

                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-3 small">
                            © TripSpoiler Admin Panel
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="{{ asset('adminkit/js/app.js') }}"></script>

</body>

</html>

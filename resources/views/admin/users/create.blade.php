@extends('layouts.admin')

@section('title', 'Yeni Kullanıcı')

@section('content')

    @if (session('success'))
        <div class="alert alert-success small">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger small">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="mb-3">
                    <label>Ad Soyad</label>
                    <input name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Kullanıcı Adı</label>
                    <input name="username" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Email</label>
                    <input name="email" type="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Rol</label>
                    <select name="role" class="form-select">
                        <option value="admin">admin</option>
                        <option value="super admin">super admin</option>
                        <option value="user">user</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Şifre</label>
                    <input name="password" type="password" class="form-control" required>
                </div>

                <button class="btn btn-primary">Kaydet</button>

            </form>

        </div>
    </div>

@endsection

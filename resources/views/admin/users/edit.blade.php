@extends('layouts.admin')

@section('title', 'Kullanıcı Düzenle')

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

            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Ad Soyad</label>
                    <input name="name" value="{{ $user->name }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Kullanıcı Adı</label>
                    <input name="username" value="{{ $user->username }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Email</label>
                    <input name="email" value="{{ $user->email }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Rol</label>
                    <select name="role" class="form-select">
                        <option value="admin" @selected($user->role == 'admin')>admin</option>
                        <option value="super_admin" @selected($user->role == 'super_admin')>super admin</option>
                        <option value="user" @selected($user->role == 'user')>user</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Yeni Şifre (opsiyonel)</label>
                    <input name="password" type="password" class="form-control">
                </div>

                <div class="text-end">
                    <button class="btn btn-primary">Güncelle</button>
                </div>

            </form>

        </div>
    </div>

@endsection

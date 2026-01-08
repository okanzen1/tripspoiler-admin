@extends('layouts.admin')

@section('title', 'Ülke Düzenle')

@section('content')

    <div class="card">
        <div class="card-body">

            @if (session('success'))
                <div class="alert alert-success small">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('countries.update', $country) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Ülke Adı</label>
                    <input name="name" value="{{ $country->name ?? '' }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <input name="slug" value="{{ $country->slug ?? '' }}" class="form-control" required>
                </div>

                <div class="form-check my-3">
                    <input type="checkbox" name="active" class="form-check-input" @checked($country->active)>
                    <label class="form-check-label">Aktif</label>
                </div>

                <button class="btn btn-primary">Güncelle</button>

            </form>

        </div>
    </div>

@endsection

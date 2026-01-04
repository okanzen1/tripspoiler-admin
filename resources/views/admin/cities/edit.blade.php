@extends('layouts.admin')

@section('title', 'Şehir Düzenle')

@section('content')

    <div class="card">
        <div class="card-body">

            @if (session('success'))
                <div class="alert alert-success small">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('cities.update', $city) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Ülke</label>
                    <select name="country_id" class="form-select">
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected($city->country_id == $country->id)>
                                {{ $country->name['en'] ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Şehir Adı</label>
                    <input name="name" value="{{ $city->name['en'] ?? '' }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <input name="slug" value="{{ $city->slug['en'] ?? '' }}" class="form-control" required>
                </div>

                <div class="form-check my-3">
                    <input type="checkbox" name="active" class="form-check-input" @checked($city->active)>

                    <label class="form-check-label">Aktif</label>
                </div>

                <button class="btn btn-primary">
                    Güncelle
                </button>

            </form>

        </div>
    </div>

@endsection

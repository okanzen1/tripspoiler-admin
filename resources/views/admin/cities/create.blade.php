@extends('layouts.admin')

@section('title', 'Yeni Şehir')

@section('content')

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('cities.store') }}">
                @csrf

                <div class="mb-3">
                    <label>Ülke</label>
                    <select name="country_id" class="form-select" required>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}">
                                {{ $country->name['en'] ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Şehir Adı</label>
                    <input name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <input name="slug" class="form-control" required>
                </div>

                <div class="form-check my-3">
                    <input type="checkbox" name="active" class="form-check-input" checked>
                    <label class="form-check-label">Aktif</label>
                </div>

                <button class="btn btn-primary">
                    Kaydet
                </button>

            </form>

        </div>
    </div>

@endsection

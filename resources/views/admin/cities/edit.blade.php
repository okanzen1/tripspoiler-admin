@extends('layouts.admin')

@section('title', 'Şehir Düzenle')

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

            <form method="POST" action="{{ route('cities.update', $city) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Ülke</label>
                    <select name="country_id" class="form-select">
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected($city->country_id == $country->id)>
                                {{ $country->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Şehir Adı</label>
                    <input name="name" value="{{ $city->name ?? '' }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <input name="slug" value="{{ $city->slug ?? '' }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="active" class="form-select">
                        <option value="1" {{ old('active', $city->active) == 1 ? 'selected' : '' }}>
                            Aktif
                        </option>
                        <option value="0" {{ old('active', $city->active) == 0 ? 'selected' : '' }}>
                            Pasif
                        </option>
                    </select>
                </div>
                
                <div class="text-end">
                    <button class="btn btn-primary">Güncelle</button>
                </div>

            </form>

        </div>
    </div>

@endsection

